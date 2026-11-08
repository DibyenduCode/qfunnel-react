# QFunnel React Frontend API Reference & Integration Guide

This guide documents every REST API endpoint integrated with the React frontend SPA (`frontend/src`), including authentication mechanisms, headers, query parameters, request payloads, response structures, and practical developer guides.

---

## 1. Architectural Overview & Client Setup

### 1.1 Base URL & Environment Handling
The React application communicates through `frontend/src/api/client.ts`.
- **Development (Vite on port 5173):** Requests to `/api/*` are reverse-proxied to the PHP backend via `vite.config.ts`.
- **Production (Apache on port 80 / Subdirectory `/qfunnel2`):** Base URL is automatically prefixed with `/qfunnel2` when running under that directory.

### 1.2 Authentication & Security Headers
Every mutating request (`POST`, `PUT`, `DELETE`) includes:
- **`credentials: 'include'`**: Sends session cookies (`PHPSESSID`).
- **`X-CSRF-Token`**: Retrieved from `/api/csrf-token` and cached in memory.
- **`Content-Type: application/json`**: Unless sending `FormData` for multipart file uploads.
- **`X-Requested-With: XMLHttpRequest`**: Standard AJAX indicator.

```typescript
import { api } from '@/api/client';

// Standard GET
const data = await api.get('/api/funnels');

// Standard POST
const result = await api.post('/api/funnels/24/mode', { funnel_mode: 'calculator' });

// Multipart File Upload
const formData = new FormData();
formData.append('media_file', file);
const uploadRes = await api.post('/api/funnels/24/upload-media', formData);
```

---

## 2. Quick Endpoint Index

| Method | Endpoint | Description | Auth Requirement |
| :--- | :--- | :--- | :--- |
| **GET** | `/api/csrf-token` | Fetch CSRF security token | Public |
| **POST** | `/api/auth/login` | Authenticate user session | Public |
| **POST** | `/api/auth/register` | Register account & default workspace | Public |
| **POST** | `/api/auth/logout` | Terminate session | Authenticated |
| **GET** | `/api/auth/me` | Current session user & workspaces | Authenticated |
| **POST** | `/api/auth/switch-workspace` | Switch active tenant/workspace | Authenticated |
| **GET** | `/api/dashboard` | Main metrics & overview stats | Tenant User |
| **GET** | `/api/analytics` | Analytics drop-offs & funnel trends | Tenant User |
| **GET** | `/api/funnels` | List workspace funnels | Tenant User |
| **POST** | `/api/funnels` | Create a new funnel | Tenant User |
| **GET** | `/api/funnels/{id}` | Funnel details & settings | Tenant User |
| **POST** | `/api/funnels/{id}/settings` | Update funnel settings & progress bar | Tenant Admin/Owner |
| **POST** | `/api/funnels/{id}/upload-logo` | Upload brand logo | Tenant Admin/Owner |
| **POST** | `/api/funnels/{id}/upload-media` | Upload step media / images | Tenant Admin/Owner |
| **POST** | `/api/media/upload` | Upload general workspace media | Tenant Admin/Owner |
| **GET** | `/api/funnels/{id}/graph` | Load Visual Builder nodes & edges | Tenant User |
| **POST** | `/api/funnels/{id}/graph` | Save Builder canvas graph | Tenant Admin/Owner |
| **POST** | `/api/funnels/{id}/mode` | Switch Quiz / Calculator mode | Tenant Admin/Owner |
| **POST** | `/api/funnels/{id}/publish` | Compile & publish funnel version | Tenant Admin/Owner |
| **POST** | `/api/funnels/{id}/unpublish` | Take funnel offline to draft | Tenant Admin/Owner |
| **POST** | `/api/funnels/{id}/duplicate` | Clone funnel & graph | Tenant Admin/Owner |
| **POST** | `/api/funnels/{id}/delete` | Soft delete funnel | Tenant Admin/Owner |
| **GET** | `/api/quiz/{slug}/manifest` | Runner manifest & theme | Public |
| **POST** | `/api/quiz/{slug}/init` | Start visitor session | Public |
| **GET** | `/api/quiz/step` | Fetch current visitor step | Public |
| **POST** | `/api/quiz/answer` | Submit quiz question answer | Public |
| **POST** | `/api/quiz/form` | Submit lead capture form | Public |
| **POST** | `/api/quiz/back` | Step backward in quiz journey | Public |
| **POST** | `/api/quiz/video-event` | Track video play/progress events | Public |
| **POST** | `/api/quiz/video-advance` | Advance video gate on CTA click | Public |
| **POST** | `/api/quiz/video-gate-submit` | Submit form embedded in video | Public |
| **GET** | `/api/submissions` | List & filter lead submissions | Tenant User |
| **GET** | `/api/submissions/{id}` | Lead answers & journey breakdown | Tenant User |
| **POST** | `/api/submissions/{id}/status` | Update lead pipeline stage | Tenant User |
| **POST** | `/api/submissions/bulk-action` | Batch update or delete leads | Tenant Admin/Owner |
| **GET** | `/api/submissions-stages` | List lead pipeline stages | Tenant User |
| **POST** | `/api/submissions-stages` | Add custom pipeline stage | Tenant Admin/Owner |
| **POST** | `/api/submissions-stages/{id}/delete` | Delete pipeline stage | Tenant Admin/Owner |
| **GET** | `/api/team` | List workspace team members | Tenant User |
| **POST** | `/api/team` | Invite new team member | Tenant Admin/Owner |
| **POST** | `/api/team/{id}/update` | Update member role | Tenant Admin/Owner |
| **POST** | `/api/team/{id}/remove` | Remove member from workspace | Tenant Admin/Owner |
| **GET** | `/api/webhooks` | List webhook endpoints | Tenant User |
| **POST** | `/api/webhooks` | Register new webhook endpoint | Tenant Admin/Owner |
| **POST** | `/api/webhooks/{id}/delete` | Delete webhook endpoint | Tenant Admin/Owner |
| **GET** | `/api/billing` | Subscription, plan & invoices | Tenant User |
| **POST** | `/api/billing/upgrade` | Upgrade subscription plan | Tenant Owner |
| **POST** | `/api/settings/workspace` | Rename workspace | Tenant Owner |
| **POST** | `/api/settings/profile` | Update profile & change password | Authenticated |
| **GET** | `/api/platform/dashboard` | Superadmin high-level metrics | Platform Superadmin |
| **GET** | `/api/platform/customers` | Superadmin tenant & user list | Platform Superadmin |
| **POST** | `/api/platform/tenants/{id}/*` | Superadmin tenant lifecycle actions | Platform Superadmin |
| **GET/POST** | `/api/platform/plans/*` | Superadmin plan management | Platform Superadmin |
| **GET/POST** | `/api/platform/payment-gateways/*` | Superadmin payment gateway setup | Platform Superadmin |
| **GET/POST** | `/api/platform/legal-pages/*` | Superadmin legal terms CMS | Platform Superadmin |
| **GET/POST** | `/api/platform/profile/*` | Superadmin account settings | Platform Superadmin |
| **GET** | `/api/platform/logs` | Superadmin audit logs | Platform Superadmin |

---

## 3. Detailed Endpoint Specifications

### 3.1 Authentication & Workspace API

#### `GET /api/csrf-token`
- **Method:** `GET`
- **Auth:** None (Public)
- **Response:**
  ```json
  {
    "success": true,
    "data": {
      "csrf_token": "a1b2c3d4..."
    }
  }
  ```

#### `POST /api/auth/login`
- **Method:** `POST`
- **Auth:** None
- **Request Body:**
  ```json
  {
    "email": "user@example.com",
    "password": "secretpassword"
  }
  ```
- **Response:**
  ```json
  {
    "success": true,
    "data": {
      "user": { "id": 1, "name": "Admin", "email": "admin@example.com" },
      "tenant": { "id": 1, "name": "Default Workspace", "role": "owner" },
      "tenants": [{ "id": 1, "name": "Default Workspace", "role": "owner" }]
    }
  }
  ```

#### `POST /api/auth/register`
- **Method:** `POST`
- **Auth:** None
- **Request Body:**
  ```json
  {
    "name": "Jane Doe",
    "email": "jane@example.com",
    "password": "Password123!",
    "workspace": "Acme Marketing"
  }
  ```

#### `GET /api/auth/me`
- **Method:** `GET`
- **Auth:** Authenticated
- **Response:**
  ```json
  {
    "success": true,
    "data": {
      "user": { "id": 1, "name": "Jane", "email": "jane@example.com" },
      "tenant": { "id": 1, "name": "Acme Marketing", "role": "owner" },
      "tenants": [{ "id": 1, "name": "Acme Marketing", "role": "owner" }],
      "is_platform_admin": false
    }
  }
  ```

#### `POST /api/auth/switch-workspace`
- **Method:** `POST`
- **Auth:** Authenticated
- **Request Body:**
  ```json
  {
    "tenant_id": 2
  }
  ```

#### `POST /api/auth/logout`
- **Method:** `POST`
- **Auth:** Authenticated
- **Request Body:** `{}`

---

### 3.2 Visual Builder & Funnel Engine API

#### `GET /api/funnels/{id}/graph`
Loads the React Flow nodes and edges for the Canvas Builder.
- **Method:** `GET`
- **Path Parameters:**
  - `id` *(number)*: Funnel ID (e.g. `24`)
- **Response:**
  ```json
  {
    "success": true,
    "data": {
      "funnel": { "id": 24, "name": "bv", "slug": "bv", "status": "published", "funnel_mode": "quiz" },
      "nodes": [
        {
          "id": "1540",
          "node_type": "start",
          "title": "Start Flow",
          "is_start": 1,
          "position_x": 0,
          "position_y": 0,
          "config": {}
        },
        {
          "id": "1541",
          "node_type": "content",
          "title": "Key Insight & Overview",
          "config": {
            "headline": "Exclusive Diagnostic",
            "primary_cta_text": "Start Free Assessment",
            "show_benefits": true
          }
        }
      ],
      "edges": [
        {
          "id": "1996",
          "source_node_id": "1540",
          "target_node_id": "1541",
          "source_handle": "output"
        }
      ]
    }
  }
  ```

#### `POST /api/funnels/{id}/graph`
Saves updated canvas coordinates, node configurations, and edges.
- **Method:** `POST`
- **Path Parameters:** `id` *(number)*
- **Request Body:**
  ```json
  {
    "nodes": [
      {
        "id": "1540",
        "type": "start",
        "title": "Start Flow",
        "position": { "x": 100, "y": 200 },
        "data": {}
      },
      {
        "id": "1541",
        "type": "content",
        "title": "Key Insight & Overview",
        "position": { "x": 420, "y": 200 },
        "data": {
          "content": {
            "headline": "Special Offer",
            "badgeText": "Exclusive",
            "primaryCtaText": "Continue",
            "mediaUrl": "/uploads/media.png",
            "benefits": ["Benefit 1", "Benefit 2"]
          }
        }
      }
    ],
    "edges": [
      {
        "source_node_id": "1540",
        "source_handle": "output",
        "source_option_id": null,
        "target_node_id": "1541"
      }
    ]
  }
  ```

#### `POST /api/funnels/{id}/publish`
Validates graph connectivity and compiles the public runtime snapshot.
- **Method:** `POST`
- **Path Parameters:** `id` *(number)*
- **Response (Success):**
  ```json
  {
    "success": true,
    "data": {
      "version_id": 284,
      "version_number": 16,
      "status": "published"
    },
    "message": "Funnel published successfully."
  }
  ```
- **Response (Unconnected Nodes Error - HTTP 400):**
  ```json
  {
    "success": false,
    "error": "Funnel has unconnected steps.",
    "error_code": "PUBLISH_FAILED",
    "data": {
      "unconnected_nodes": ["Node #1542 (Interactive Video) has no exit path"]
    }
  }
  ```

#### `POST /api/funnels/{id}/settings`
Updates general funnel settings, SEO metadata, brand color, and progress bar preferences.
- **Method:** `POST`
- **Path Parameters:** `id` *(number)*
- **Request Body:**
  ```json
  {
    "name": "Health Assessment 2026",
    "slug": "health-audit",
    "settings": {
      "brand_color": "#1b6c80",
      "font_family": "Inter",
      "show_progress_bar": true,
      "seo_title": "Take Free Audit",
      "seo_description": "Instant customized score in 2 minutes",
      "custom_css": "",
      "head_scripts": ""
    }
  }
  ```

#### `POST /api/funnels/{id}/upload-media`
Uploads step media (images/videos) to the server.
- **Method:** `POST`
- **Path Parameters:** `id` *(number)*
- **Request Body:** `multipart/form-data`
  - `media_file`: Binary file (JPEG, PNG, WebP, MP4)
- **Response:**
  ```json
  {
    "success": true,
    "data": {
      "url": "http://localhost/qfunnel2/storage/uploads/media_abc123.png",
      "relative_url": "/storage/uploads/media_abc123.png"
    }
  }
  ```

---

### 3.3 Public Quiz & Funnel Runner API (Visitor Flow)

#### `GET /api/quiz/{slug}/manifest`
Retrieves public funnel styling, logo, settings, and first step.
- **Method:** `GET`
- **Path Parameters:** `slug` *(string)*: e.g. `bv`
- **Response:**
  ```json
  {
    "success": true,
    "data": {
      "funnel_id": 24,
      "title": "bv",
      "settings": {
        "brand_color": "#1b6c80",
        "show_progress_bar": true
      },
      "first_step_id": 1540
    }
  }
  ```

#### `POST /api/quiz/{slug}/init`
Initiates a new visitor session and tracks referrer/UTM parameters.
- **Method:** `POST`
- **Path Parameters:** `slug` *(string)*
- **Request Body:**
  ```json
  {
    "referrer": "https://google.com",
    "utm_source": "facebook",
    "utm_medium": "cpc",
    "utm_campaign": "spring_sale"
  }
  ```
- **Response:**
  ```json
  {
    "success": true,
    "data": {
      "session_token": "sess_89f1a23c4d5e...",
      "current_step": {
        "id": 1541,
        "type": "content",
        "title": "Key Insight & Overview",
        "settings": { ... },
        "total_steps": 3,
        "current_step_index": 1
      }
    }
  }
  ```

#### `GET /api/quiz/step`
Fetches current state of the visitor's session.
- **Method:** `GET`
- **Query Parameters:**
  - `session_token` *(string, required)*
- **Response:**
  ```json
  {
    "success": true,
    "data": {
      "step": {
        "id": 1542,
        "type": "video_gate",
        "title": "Interactive Video",
        "video_gate": {
          "video_url": "https://www.youtube.com/watch?v=xyz",
          "cta_reveal": "time",
          "cta_delay_seconds": 285,
          "cta_button_text": "Claim Your Quote"
        },
        "total_steps": 3,
        "current_step_index": 2
      },
      "is_completed": false
    }
  }
  ```

#### `POST /api/quiz/answer`
Submits question choice, numeric slider value, or continues from a Mini Landing Page node.
- **Method:** `POST`
- **Request Body:**
  ```json
  {
    "session_token": "sess_89f1a23c4d5e...",
    "step_id": 1541,
    "option_id": 312,
    "answer_value": "Option A"
  }
  ```
- **Response:**
  ```json
  {
    "success": true,
    "data": {
      "next_step": { "id": 1542, "type": "video_gate" },
      "is_completed": false
    }
  }
  ```

#### `POST /api/quiz/form`
Captures lead form details and stores the submission in CRM.
- **Method:** `POST`
- **Request Body:**
  ```json
  {
    "session_token": "sess_89f1a23c4d5e...",
    "fields": {
      "name": "Dibyendu Roy",
      "email": "dibyendu@example.com",
      "phone": "+919932982002"
    }
  }
  ```

#### `POST /api/quiz/video-event`
Tracks video viewing metrics.
- **Method:** `POST`
- **Request Body:**
  ```json
  {
    "session_token": "sess_89f1a23c4d5e...",
    "step_id": 1542,
    "event_type": "cta_revealed",
    "current_time": 285,
    "duration": 300
  }
  ```

#### `POST /api/quiz/video-advance`
Advances the visitor after video CTA click.
- **Method:** `POST`
- **Request Body:**
  ```json
  {
    "session_token": "sess_89f1a23c4d5e...",
    "step_id": 1542
  }
  ```

---

### 3.4 Leads & Submissions CRM API

#### `GET /api/submissions`
Lists leads with search and stage filters.
- **Method:** `GET`
- **Query Parameters:**
  - `funnel_id` *(number, optional)*
  - `status` *(string, optional)*: e.g. `'new'`, `'contacted'`, `'qualified'`
  - `search` *(string, optional)*
  - `page` *(number, default: 1)*
  - `limit` *(number, default: 20)*
- **Response:**
  ```json
  {
    "success": true,
    "data": {
      "data": [
        {
          "id": 105,
          "funnel_id": 24,
          "name": "Dibyendu Roy",
          "email": "dibyendu@example.com",
          "phone": "+919932982002",
          "lead_status": "new",
          "created_at": "2026-11-08 21:15:00"
        }
      ],
      "total": 1,
      "page": 1,
      "per_page": 20,
      "stages": [
        { "id": 1, "name": "New", "color": "#3b82f6" },
        { "id": 2, "name": "Contacted", "color": "#eab308" },
        { "id": 3, "name": "Qualified", "color": "#22c55e" }
      ]
    }
  }
  ```

#### `POST /api/submissions/{id}/status`
- **Method:** `POST`
- **Request Body:**
  ```json
  {
    "lead_status": "qualified"
  }
  ```

#### `POST /api/submissions/bulk-action`
- **Method:** `POST`
- **Request Body:**
  ```json
  {
    "action": "update_status",
    "submission_ids": [101, 102, 105],
    "status": "contacted"
  }
  ```

---

### 3.5 Team & Collaboration API

#### `GET /api/team`
- **Method:** `GET`
- **Response:**
  ```json
  {
    "success": true,
    "data": [
      { "id": 1, "user_id": 1, "name": "Admin", "email": "admin@example.com", "role": "owner" },
      { "id": 2, "user_id": 5, "name": "Alex", "email": "alex@example.com", "role": "editor" }
    ]
  }
  ```

#### `POST /api/team`
- **Method:** `POST`
- **Request Body:**
  ```json
  {
    "email": "sarah@company.com",
    "role": "editor"
  }
  ```

#### `POST /api/team/{id}/update`
- **Method:** `POST`
- **Path Parameters:** `id` *(membership ID)*
- **Request Body:**
  ```json
  {
    "role": "admin"
  }
  ```

---

### 3.6 Webhooks API

#### `GET /api/webhooks`
- **Method:** `GET`
- **Response:**
  ```json
  {
    "success": true,
    "data": [
      {
        "id": 12,
        "url": "https://hooks.zapier.com/hooks/catch/123/xyz",
        "events": ["submission.created"],
        "is_active": 1,
        "created_at": "2026-11-01 10:00:00"
      }
    ]
  }
  ```

#### `POST /api/webhooks`
- **Method:** `POST`
- **Request Body:**
  ```json
  {
    "url": "https://hooks.zapier.com/hooks/catch/123/xyz",
    "events": ["submission.created", "quiz.completed"],
    "secret": "whsec_customkey"
  }
  ```

---

### 3.7 Billing & Customer Subscriptions API

#### `GET /api/billing`
- **Method:** `GET`
- **Response:**
  ```json
  {
    "success": true,
    "data": {
      "current_plan": {
        "id": 2,
        "name": "Growth Pro",
        "price": 49.00,
        "billing_cycle": "monthly",
        "features": { "calculator_mode": true, "remove_branding": true }
      },
      "usage": {
        "funnels_count": 5,
        "funnels_limit": 20,
        "submissions_count": 340,
        "submissions_limit": 2500
      },
      "plans": [ ... ],
      "invoices": [ ... ]
    }
  }
  ```

#### `POST /api/billing/upgrade`
- **Method:** `POST`
- **Request Body:**
  ```json
  {
    "plan_id": 3
  }
  ```

---

### 3.8 Superadmin Platform API (`/api/platform/*`)
*Available exclusively to users with `is_platform_admin = 1`.*

| Method | Endpoint | Payload / Description |
| :--- | :--- | :--- |
| `GET` | `/api/platform/dashboard` | Returns platform MRR, total tenants, active users, and system load. |
| `GET` | `/api/platform/customers` | Returns all tenants, owners, subscriptions, and payment history. |
| `POST` | `/api/platform/tenants/{id}/toggle` | Disables or enables tenant workspace. |
| `POST` | `/api/platform/tenants/{id}/assign-plan` | `{ "plan_id": 2, "billing_cycle": "yearly" }` |
| `POST` | `/api/platform/tenants/{id}/suspend` | `{ "reason": "Non-payment" }` |
| `POST` | `/api/platform/tenants/{id}/unsuspend` | Re-enables suspended tenant workspace. |
| `POST` | `/api/platform/tenants/{id}/record-payment` | `{ "amount": 99.00, "notes": "Bank transfer", "payment_method": "manual" }` |
| `GET` | `/api/platform/plans` | Lists all platform plans. |
| `POST` | `/api/platform/plans` | Creates a new subscription tier with price and feature flags. |
| `POST` | `/api/platform/plans/{id}/update` | Edits subscription tier price and limits. |
| `GET/POST` | `/api/platform/payment-gateways` | Configure Stripe, Razorpay, or Manual QR code details. |
| `POST` | `/api/platform/payment-gateways/upload-qr` | `FormData` with `qr_image` file for manual UPI/Bank QR. |
| `GET/POST` | `/api/platform/legal-pages` | Dynamic CMS for Terms of Service, Privacy Policy, and Refund Policy. |
| `GET` | `/api/platform/logs` | Security and audit log stream. |

---

## 4. Frontend Integration Best Practices

### 4.1 Error Handling Standard
When calling any endpoint via `api.*`, always catch and differentiate between network outages and validation messages:

```typescript
try {
  const res = await api.post(`/api/funnels/${id}/publish`);
  setBannerMsg({ type: 'success', text: 'Published successfully!' });
} catch (err: any) {
  const errMsg = err.message || '';
  const isNetwork = errMsg.toLowerCase().includes('fetch') || errMsg.toLowerCase().includes('network');

  if (isNetwork) {
    // Show network banner
    setBannerMsg({ type: 'error', text: 'Network connection issue. Please check your server and try again.' });
  } else if (err.data?.unconnected_nodes) {
    // Show validation list
    setValidationErrors(err.data.unconnected_nodes);
  } else {
    setBannerMsg({ type: 'error', text: errMsg });
  }
}
```

### 4.2 Loading State Indicator
Use `subscribeToLoading` from `@/api/client` to trigger global progress bars or spinners:

```typescript
import { subscribeToLoading } from '@/api/client';

useEffect(() => {
  const unsubscribe = subscribeToLoading((activeCount) => {
    setIsGlobalLoading(activeCount > 0);
  });
  return unsubscribe;
}, []);
```
