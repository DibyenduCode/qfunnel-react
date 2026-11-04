# QFunnel — API, Webhook & Embedding Documentation (Easy Guide)

This guide explains how to connect QFunnel with your CRM, trigger real-time webhooks, and embed interactive quizzes on any website (WordPress, Shopify, Webflow, etc.). Written in simple, easy-to-understand English.

---

## 1. REST API (`/api/v1/*`)

### What is the API?
> **Analogy**: Think of an API like a **waiter at a restaurant**. Your CRM or software sits at the table and tells the waiter: *"Bring me all new leads from Quiz #1"*. The waiter fetches the fresh leads from QFunnel and brings them back to your software in clean data format (JSON).

### How to Authenticate
Send your API Key in your request headers. You can use either method:

```http
Authorization: Bearer YOUR_API_KEY
```
or
```http
X-API-Key: YOUR_API_KEY
```

---

### Endpoints

#### 1. Export Quiz Submissions & Leads
* **Method**: `GET`
* **URL**: `/api/v1/submissions`
* **Query Parameters**:
  * `funnel_id` (optional): Filter leads by specific quiz ID (e.g. `?funnel_id=1`).
  * `limit` (optional): Max leads to return (default: `25`, max: `100`).

**Example Request**:
```bash
curl -X GET "https://yourdomain.com/api/v1/submissions?limit=10" \
  -H "Authorization: Bearer YOUR_API_KEY"
```

**Example JSON Response**:
```json
{
  "status": "success",
  "count": 1,
  "data": [
    {
      "session_id": 142,
      "funnel_id": 1,
      "funnel_name": "SaaS Lead Qualification Quiz",
      "status": "completed",
      "submitted_at": "2026-10-17 14:32:00",
      "form_fields": [
        { "field": "full_name", "label": "Full Name", "value": "Alex Morgan" },
        { "field": "email", "label": "Business Email", "value": "alex@company.com" },
        { "field": "phone", "label": "Phone Number", "value": "+1-555-0199" }
      ]
    }
  ]
}
```

#### 2. List All Active Quizzes
* **Method**: `GET`
* **URL**: `/api/v1/funnels`

```bash
curl -X GET "https://yourdomain.com/api/v1/funnels" \
  -H "Authorization: Bearer YOUR_API_KEY"
```

#### 3. Check Account Status & Quota
* **Method**: `GET`
* **URL**: `/api/v1/me`

---

## 2. Webhooks ("Web huk")

### What is a Webhook?
> **Analogy**: Think of a Webhook like an **instant phone notification**. Instead of your software repeatedly asking: *"Did someone fill out my quiz yet?"*, QFunnel calls your server the exact millisecond a visitor clicks Submit and hands you the lead.

### Lifecycle Diagram
```
[Visitor completes Quiz] ──► [QFunnel generates HMAC signature] ──► [HTTP POST to your Webhook URL]
                                                                             │
                                                                   [Success: 200 OK]
                                                                   [Failure: Retries 5 times]
```

### Webhook JSON Payload
```json
{
  "event": "quiz.completed",
  "event_id": "evt_98fbc12093ea",
  "timestamp": "2026-10-17T14:35:12Z",
  "funnel": {
    "id": 1,
    "name": "SaaS Lead Qualification Funnel",
    "slug": "saas-lead-quiz"
  },
  "session": {
    "id": 142,
    "score": 85,
    "started_at": "2026-10-17 14:33:02",
    "completed_at": "2026-10-17 14:35:12",
    "utm_source": "google_ads",
    "utm_campaign": "fall_lead_gen"
  },
  "lead": {
    "full_name": "Sarah Connor",
    "email": "sarah@cyberdyne.com",
    "phone": "+1-555-8392"
  },
  "answers": [
    { "question": "What is your monthly volume?", "answer": "100 - 1,000 leads / month", "points": 25 }
  ]
}
```

### Security: Signature Verification (PHP)
To prevent impostors from sending fake leads, verify the `X-QFunnel-Signature` header:

```php
$payload = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_QFUNNEL_SIGNATURE'] ?? '';
$secret = 'YOUR_WEBHOOK_SECRET';

$expectedSignature = hash_hmac('sha256', $payload, $secret);

if (!hash_equals($expectedSignature, $signature)) {
    http_response_code(401);
    die('Invalid signature!');
}

// 100% verified authentic QFunnel lead!
$leadData = json_decode($payload, true);
http_response_code(200);
```

### Auto-Retry Schedule (5 Attempts)
If your server returns an error or times out:
1. **Try 1**: Instant
2. **Try 2**: 1 minute later
3. **Try 3**: 5 minutes later
4. **Try 4**: 30 minutes later
5. **Try 5**: 2 hours later

---

## 3. Website Embedding ("Embading")

### What is Embedding?
> **Analogy**: Think of embedding like **hanging a picture frame on your living room wall**. Your website (WordPress, Shopify, etc.) is the wall, and the quiz is the picture inside the frame. Visitors take your interactive quiz directly without ever leaving your website.

---

### Mode 1: Inline Widget (Recommended)
Embeds directly inside any blog post, landing page, or product page.

```html
<!-- QFunnel Inline Embed Widget -->
<div style="width: 100%; max-width: 800px; margin: 0 auto;">
  <iframe 
    src="https://yourdomain.com/quiz/YOUR_QUIZ_SLUG" 
    style="width: 100%; min-height: 600px; border: none; border-radius: 16px; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1);" 
    allow="camera; microphone; autoplay; fullscreen"
    loading="lazy">
  </iframe>
</div>
```

---

### Mode 2: Floating Popup Modal
Adds a sleek floating button in the bottom-right corner that opens the quiz in a modal popup.

```html
<!-- Floating Button -->
<button onclick="document.getElementById('qfunnelModal').style.display='flex'" 
  style="position: fixed; bottom: 24px; right: 24px; z-index: 99999; padding: 14px 22px; background: linear-gradient(135deg, #6366f1, #4f46e5); color: #fff; font-weight: bold; border: none; border-radius: 50px; box-shadow: 0 10px 25px rgba(99,102,241,0.5); cursor: pointer; font-family: sans-serif;">
  💬 Take 60s Quiz
</button>

<!-- Modal Lightbox -->
<div id="qfunnelModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.7); backdrop-filter: blur(6px); z-index: 100000; align-items: center; justify-content: center; padding: 16px;">
  <div style="position: relative; width: 100%; max-width: 750px; background: #fff; border-radius: 20px; overflow: hidden;">
    <button onclick="document.getElementById('qfunnelModal').style.display='none'" style="position: absolute; top: 14px; right: 16px; background: #f1f5f9; border: none; font-size: 18px; width: 32px; height: 32px; border-radius: 50%; cursor: pointer; z-index: 10;">✕</button>
    <iframe src="https://yourdomain.com/quiz/YOUR_QUIZ_SLUG" style="width: 100%; height: 650px; border: none;" allow="camera; microphone; autoplay; fullscreen"></iframe>
  </div>
</div>
```

---

### Step-by-Step Platform Guides

#### WordPress
1. Open your page in WordPress.
2. Click **+** (Add block) and choose **Custom HTML** (or drag the **HTML widget** if using Elementor).
3. Paste the embed code snippet.
4. Click **Update** or **Publish**.

#### Shopify
1. In Shopify Admin, open **Online Store -> Pages**.
2. Click **Add page** or edit an existing one.
3. In the rich-text toolbar, click the **Show HTML (`<>`)** button.
4. Paste the embed code and click **Save**.
5. *(Optional in Theme Customizer)*: Click **Add section -> Custom Liquid** and paste.

#### Webflow / Wix / Squarespace
1. Add an **Embed / HTML** block element onto the page.
2. Paste the snippet and publish your changes.
