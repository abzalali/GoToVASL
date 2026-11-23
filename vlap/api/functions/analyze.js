// Netlify Function for the VLAP v0.2 demo.
// Proxies requests to Groq so GROQ_API_KEY remains server-side.

const CORS = {
  'Access-Control-Allow-Origin': '*',
  'Access-Control-Allow-Methods': 'POST, OPTIONS',
  'Access-Control-Allow-Headers': 'Content-Type',
};

const SYSTEM = `You are VLAP (Vasl Language Analysis Platform), a culturally-competent NLP system trained on BIPOC, LGBTQ+, and underserved youth language to detect behavioral health distress signals.

Analyze the user message and return ONLY a valid JSON object - no preamble, no markdown, no explanation:

{
  "risk_score": <float 0.0-1.0>,
  "risk_tier": <"crisis"|"high"|"moderate"|"clear">,
  "signals": [{"code": "<code>", "label": "<label>"}],
  "cultural_context": "<cultural/linguistic patterns detected, or null>",
  "member_response": "<warm human response the member sees - no AI mention, no clinical language>",
  "recommended_action": "<action for the clinical team>"
}

Signal taxonomy:
SHA-01 Direct suicidal ideation | SHA-02 Passive ideation | SHA-03 Coded ideation (e.g. "unaliving") | SHA-04 Self-harm reference
CRS-01 Acute crisis | CRS-02 Crisis escalation risk
HOP-01 Learned helplessness | HOP-02 Future foreclosure | HOP-03 Existential hopelessness
ISO-01 Social withdrawal | ISO-02 Disconnection | ISO-03 Abandonment fear
CCM-01 Stigma barrier | CCM-02 Pre-disclosure hedge | CCM-03 AAVE minimization inversion | CCM-04 Family/community pressure

Risk tiers: crisis (score 0.80-1.0) | high (0.55-0.79) | moderate (0.30-0.54) | clear (0.0-0.29)
Return ONLY the JSON object.`;

function json(statusCode, body, headers = {}) {
  return {
    statusCode,
    headers: Object.assign({ 'Content-Type': 'application/json' }, CORS, headers),
    body: JSON.stringify(body),
  };
}

exports.handler = async function handler(event) {
  if (event.httpMethod === 'OPTIONS') {
    return { statusCode: 204, headers: CORS, body: '' };
  }

  if (event.httpMethod !== 'POST') {
    return json(405, { error: 'Method not allowed' });
  }

  const apiKey = process.env.GROQ_API_KEY;
  if (!apiKey) {
    return json(500, { error: 'API key not configured' });
  }

  let body;
  try {
    body = JSON.parse(event.body || '{}');
  } catch {
    return json(400, { error: 'Invalid JSON body' });
  }

  const message = (body.message || '').trim();
  if (!message) {
    return json(400, { error: 'Message required' });
  }

  try {
    const resp = await fetch('https://api.groq.com/openai/v1/chat/completions', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        Authorization: `Bearer ${apiKey}`,
      },
      body: JSON.stringify({
        model: 'openai/gpt-oss-20b',
        max_tokens: 1024,
        response_format: { type: 'json_object' },
        messages: [
          { role: 'system', content: SYSTEM },
          { role: 'user', content: message },
        ],
      }),
    });

    const data = await resp.json();
    if (!resp.ok) {
      return json(resp.status, { error: data?.error?.message || 'Groq API error' });
    }

    const raw = data?.choices?.[0]?.message?.content || '';
    const clean = raw.replace(/```json|```/g, '').trim();

    let parsed;
    try {
      parsed = JSON.parse(clean);
    } catch {
      const match = clean.match(/\{[\s\S]*\}/);
      if (!match) {
        return json(500, { error: 'Unexpected model response format' });
      }

      try {
        parsed = JSON.parse(match[0]);
      } catch {
        return json(500, { error: 'Could not parse model output' });
      }
    }

    return json(200, parsed);
  } catch (e) {
    return json(502, { error: e.message || 'Upstream error' });
  }
};
