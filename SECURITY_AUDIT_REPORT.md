# 🛡️ সম্পূর্ণ সিস্টেম ও এপিআই সিকিউরিটি অডিট রিপোর্ট (Comprehensive Security Audit & Hardening Report)

**তারিখ:** ১৮ নভেম্বর ২০২৬  
**অ্যাপ্লিকেশন:** Quiz Funnel SaaS (FilterFunnel / QFunnel)  
**আর্কিটেকচার:** PHP 8.2+ কাস্টম MVC ব্যাকএন্ড, React 18 SPA ফ্রন্টএন্ড, MariaDB/MySQL ডাটাবেজ  
**অডিট স্ট্যান্ডার্ড:** OWASP Top 10 (Web Applications) ও OWASP API Security Top 10  
**স্ট্যাটাস:** ✅ সম্পূর্ণ অডিট সম্পন্ন এবং চিহ্নিত প্রতিটি দুর্বলতা কোডে স্থায়ীভাবে সমাধান (Remediated & Hardened) করা হয়েছে।

---

## ১. নির্বাহী সারসংক্ষেপ (Executive Summary)

অ্যাপ্লিকেশনের ব্যাকএন্ড সোর্স কোড, ডাটাবেজ কোয়েরি আর্কিটেকচার, পাবলিক ও প্রাইভেট REST API এন্ডপয়েন্টস, ফাইল আপলোড পাইপলাইন, সেশন ও কুকি ম্যানেজমেন্ট এবং ফ্রন্টএন্ড ক্লায়েন্ট-সাইড ইন্টিগ্রেশন গভীর বিশ্লেষণের মাধ্যমে অডিট করা হয়েছে। 

অডিটে কোডবেসের বিদ্যমান শক্তিশালী দিকগুলো যাচাই করার পাশাপাশি বেশ কিছু গুরুত্বপূর্ণ নিরাপত্তা দুর্বলতা (CORS Misconfiguration, File Upload Script Execution Risk, Broken Function Level Authorization/BFLA, Rate Limiting-এর অনুপস্থিতি, এবং CSV Formula Injection) চিহ্নিত করা হয়। চিহ্নিত প্রতিটি সমস্যা সুনির্দিষ্ট ডিফেন্সিভ মেকানিজম প্রয়োগের মাধ্যমে সমাধান করা হয়েছে।

---

## ২. অডিট ম্যাট্রিক্স ও নিরীক্ষা ড্যাশবোর্ড (Audit Matrix)

| নিরাপত্তা বিভাগ (Security Domain) | প্রাথমিক অবস্থা (Before) | বর্তমান অবস্থা (After) | ঝুঁকির মাত্রা | প্রভাবিত প্রধান ফাইল |
| :--- | :--- | :--- | :---: | :--- |
| **১. CORS & Security Headers** | ⚠️ `!empty($origin)` থাকার কারণে অনিরাপদ ক্রেডেনশিয়াল রিড সম্ভব ছিল | ✅ শুধুমাত্র ভেরিফাইড হোস্ট ও অনুমোদিত ডোমেইন ক্রেডেনশিয়ালসহ সমর্থিত | **High** | [public/index.php](file:///c:/xampp/htdocs/qfunnel2/public/index.php) |
| **২. File Upload & RCE** | ⚠️ আপলোড ডিরেক্টরিতে স্ক্রিপ্ট এক্সিকিউশন ব্লক ছিল না এবং ক্লায়েন্ট এক্সটেনশন ট্রাস্ট করা হচ্ছিল | ✅ `.htaccess` স্ক্রিপ্ট ব্যারিয়ার, কঠোর MIME-টু-এক্সটেনশন ম্যাপিং ও SVG স্যানিটাইজার সক্রিয় | **Critical** | [public/uploads/.htaccess](file:///c:/xampp/htdocs/qfunnel2/public/uploads/.htaccess), [ApiSpaController.php](file:///c:/xampp/htdocs/qfunnel2/app/Controllers/ApiSpaController.php) |
| **৩. Login Brute Force** | ⚠️ লগইন ব্যর্থতার কোনো লিমিট ছিল না | ✅ ১৫ মিনিটে ৫ বার ভুল চেষ্টায় অ্যাকাউন্ট আইপি ও ইমেইল লকআউট | **High** | [AuthService.php](file:///c:/xampp/htdocs/qfunnel2/app/Services/AuthService.php), [016_login_rate_limiting.sql](file:///c:/xampp/htdocs/qfunnel2/database/migrations/016_login_rate_limiting.sql) |
| **৪. CSRF Protection** | ⚠️ মিডলওয়্যার থাকলেও রাউটে সক্রিয় ছিল না | ✅ সমস্ত স্টেট-চেঞ্জিং অথেনটিকেটেড রুটে `CsrfMiddleware` যুক্ত | **High** | [routes/web.php](file:///c:/xampp/htdocs/qfunnel2/routes/web.php), [CsrfMiddleware.php](file:///c:/xampp/htdocs/qfunnel2/app/Middleware/CsrfMiddleware.php) |
| **৫. Function Level Auth (BFLA/RBAC)** | ⚠️ সাধারণ মেম্বার ফানেল ডিলিট, পাবলিশ ও টিম রোল বদলাতে পারত | ✅ ১৪+ টি স্পর্শকাতর রুটে কঠোর RBAC পারমিশন বাধ্যতামূলক করা হয়েছে | **Critical** | [ApiSpaController.php](file:///c:/xampp/htdocs/qfunnel2/app/Controllers/ApiSpaController.php), [AuthorizationService.php](file:///c:/xampp/htdocs/qfunnel2/app/Services/AuthorizationService.php) |
| **৬. DoS & Resource Limiting** | ⚠️ REST API, রেজিস্ট্রেশন এবং সাপোর্টে কোনো থ্রটলিং ছিল না | ✅ নতুন ডিস্ট্রিবিউটেড `RateLimiterService` দিয়ে স্লাইডিং উইন্ডো লিমিট সক্রিয় | **Medium** | [RateLimiterService.php](file:///c:/xampp/htdocs/qfunnel2/app/Services/RateLimiterService.php), [ApiController.php](file:///c:/xampp/htdocs/qfunnel2/app/Controllers/ApiController.php) |
| **৭. CSV Formula Injection** | ⚠️ লিড নাম `=`, `+`, `-`, `@` দিয়ে শুরু হলে এক্সেল ফর্মুলা হিসেবে রান হতো | ✅ CWE-1236 অনুযায়ী প্রিফিক্স কোটেশন স্যানিটাইজেশন কার্যকর | **Medium** | [SubmissionService.php](file:///c:/xampp/htdocs/qfunnel2/app/Services/SubmissionService.php) |
| **৮. Cron Job Authentication** | ⚠️ হার্ডকোডেড ডিফল্ট সিক্রেট এবং টাইমিং অ্যাটাক ঝুঁকি ছিল | ✅ ক্রিপ্টোগ্রাফিক `hash_equals()` এবং হেডার সাপোর্ট যুক্ত | **Medium** | [ApiSpaController.php](file:///c:/xampp/htdocs/qfunnel2/app/Controllers/ApiSpaController.php) |
| **৯. SSRF Protection** | ⚠️ প্রোডাকশন পরিবেশ বাদে অন্যখানে ডিফল্ট ব্লকিং ছিল না | ✅ "Secure by Default" নীতিতে রিজার্ভড/লুপব্যাক আইপি সম্পূর্ণ ব্লকড | **High** | [WebhookService.php](file:///c:/xampp/htdocs/qfunnel2/app/Services/WebhookService.php) |
| **১০. SQL Injection (SQLi)** | ✅ শতভাগ নিরাপদ | সম্পূর্ণ অ্যাপ্লিকেশনে PDO প্যারামিটার বাইন্ডিং (`?`) বিদ্যমান | **None** | [Database.php](file:///c:/xampp/htdocs/qfunnel2/app/Core/Database.php) |
| **১১. Multi-Tenant Isolation (BOLA)** | ✅ শতভাগ নিরাপদ | সেশন-এনফোর্সড `tenant_id` দিয়ে প্রতিটি কুয়েরি আইসোলেটেড | **None** | [TenantContextService.php](file:///c:/xampp/htdocs/qfunnel2/app/Services/TenantContextService.php) |

---

## ৩. চিহ্নিত নিরাপত্তা সমস্যা ও প্রয়োগকৃত প্রতিকার (Detailed Remediations)

### ৩.১. CORS পলিসি হার্ডেনিং ও নিরাপত্তা হেডার্স
* **সমস্যা:** [public/index.php](file:///c:/xampp/htdocs/qfunnel2/public/index.php)-এ `|| !empty($origin)` শর্ত থাকায় বহিরাগত যেকোনো ওয়েবসাইট (যেমন `attacker.com`) থেকে ব্রাউজারে ভিকটিমের কুকি সহ রিকোয়েস্ট পাঠিয়ে রেসপন্স ডাটা রিড করা সম্ভব ছিল।
* **সমাধান:** 
  1. ক্রেডেনশিয়াল সমৃদ্ধ (`Access-Control-Allow-Credentials: true`) অনুমোদন শুধুমাত্র লোকাল ডেভ অরিজিন (`localhost:5173`, ইত্যাদি) এবং বর্তমান সার্ভারের ডোমেইন (`HTTP_HOST` ও `APP_URL`)-এর জন্য সীমাবদ্ধ করা হয়েছে।
  2. পাবলিক ফানেল রুটগুলোর (`/api/public/*`) ক্ষেত্রে সাধারণ `Access-Control-Allow-Origin: *` রাখা হয়েছে ক্রেডেনশিয়াল ছাড়া, যাতে বাহ্যিক ল্যান্ডিং পেজে ফর্ম এমবেড কাজ করে কিন্তু সেশন ডেটা চুরি না হয়।
  3. জরুরি সিকিউরিটি হেডার যুক্ত করা হয়েছে:
     - `X-Content-Type-Options: nosniff`: ব্রাউজার কর্তৃক ক্ষতিকর ফাইলকে অন্য মাইম টাইপ ভেবে রান করা রোধ করে।
     - `X-XSS-Protection: 1; mode=block`: ব্রাউজারের রিফ্লেক্টেড XSS ফিল্টার কার্যকর করে।
     - `Referrer-Policy: strict-origin-when-cross-origin`: রেফারার হেডারে সংবেদনশীল টোকেন ফাঁস বন্ধ করে।

---

### ৩.২. ফাইল আপলোড সুরক্ষা ও রিমোট কোড এক্সিকিউশন (RCE) প্রতিরোধ
* **সমস্যা:** ব্যবহারকারীর আপলোড করা ফাইলের মূল নামের এক্সটেনশন ব্যবহার করা হতো এবং [public/uploads/](file:///c:/xampp/htdocs/qfunnel2/public/uploads/) ফোল্ডারে অ্যাপাচি স্ক্রিপ্ট ইঞ্জিন সক্রিয় ছিল।
* **সমাধান:**
  1. **Apache Engine Barrier:** [public/uploads/.htaccess](file:///c:/xampp/htdocs/qfunnel2/public/uploads/.htaccess) ফাইল স্থাপন করা হয়েছে যা `php_flag engine off`, `SetHandler default-handler` এবং `<FilesMatch>` দিয়ে `.php`, `.phtml`, `.phar`, `.sh`, `.cgi` ইত্যাদির এক্সিকিউশন নিষিদ্ধ করে।
  2. **ভেরিফাইড MIME ম্যাপিং:** ক্লায়েন্টের ফাইলের নামের এক্সটেনশন বাদ দিয়ে সার্ভারে `finfo_file()` বা `mime_content_type()` দিয়ে যাচাইকৃত MIME টাইপ অনুযায়ী কঠোরভাবে এক্সটেনশন তৈরি করা হচ্ছে (`image/png` $\rightarrow$ `png`, `image/jpeg` $\rightarrow$ `jpg`, ইত্যাদি)।
  3. **SVG স্ক্রিপ্ট স্যানিটাইজার:** [ApiSpaController.php](file:///c:/xampp/htdocs/qfunnel2/app/Controllers/ApiSpaController.php)-এ `isSvgSafe()` মেথড যুক্ত করা হয়েছে, যা SVG ফাইলে লুকানো `<script>`, `<foreignObject>`, `<iframe`, `javascript:`, বা ইভেন্ট হ্যান্ডলার (`onload=`, `onerror=`) পেলে সাথে সাথে ফাইলটি বাতিল (`400 Bad Request`) করে।

---

### ৩.৩. লগইন ব্রুট-ফোর্স প্রতিরক্ষা ও রেট লিমিটিং
* **সমস্যা:** `/api/auth/login` এন্ডপয়েন্টে পাসওয়ার্ড অনুমানের কোনো সীমা ছিল না, ফলে ক্র্যাক বট বা ডিকশনারি অ্যাটাক চালানো সম্ভব ছিল।
* **সমাধান:**
  1. ডাটাবেজে [016_login_rate_limiting.sql](file:///c:/xampp/htdocs/qfunnel2/database/migrations/016_login_rate_limiting.sql) মাইগ্রেশনের মাধ্যমে ইনডেক্সড `login_attempts` টেবিল যুক্ত করা হয়েছে।
  2. [AuthService.php](file:///c:/xampp/htdocs/qfunnel2/app/Services/AuthService.php)-এ ১৫ মিনিটের স্লাইডিং উইন্ডোতে সর্বোচ্চ **৫ বার** ব্যর্থ প্রচেষ্টার সীমা আরোপ করা হয়েছে। ৫ বার ভুল হলে একাউন্ট ও আইপি ১৫ মিনিটের জন্য সাময়িক লকআউট হবে।
  3. সফল লগইনে পূর্বের ব্যর্থ রেকর্ড স্বয়ংক্রিয়ভাবে মুছে ফেলা হয়।

---

### ৩.৪. ক্রস-সাইট রিকোয়েস্ট ফোরজারি (CSRF) প্রতিরোধ
* **সমস্যা:** [CsrfMiddleware.php](file:///c:/xampp/htdocs/qfunnel2/app/Middleware/CsrfMiddleware.php) ক্লাসে টোকেন যাচাইয়ের কোড থাকলেও [routes/web.php](file:///c:/xampp/htdocs/qfunnel2/routes/web.php)-এ মিডলওয়্যারটি সংযুক্ত ছিল না।
* **সমাধান:**
  1. সমস্ত অথেনটিকেটেড API রুট গ্রুপে `CsrfMiddleware::class` বাইন্ড করা হয়েছে।
  2. `POST`, `PUT`, `DELETE`, `PATCH` রিকোয়েস্টে প্রেরিত `X-CSRF-Token` বা `X-XSRF-Token` হেডারের মান ক্রিপ্টোগ্রাফিক `hash_equals()` দিয়ে যাচাই করা হয়।
  3. বহিরাগত ক্ষতিকর ওয়েবসাইট থেকে লগইন করা ব্যবহারকারীর সেশন ব্যবহার করে ব্যাকগ্রাউন্ডে কোনো অ্যাকশন চালানো অসম্ভব।

---

### ৩.৫. ব্রোকেন ফাংশন-লেভেল অথরাইজেশন (BFLA / RBAC) দূরীকরণ
* **সমস্যা:** একটি মাল্টি-ইউজার ওয়ার্কস্পেসের সাধারণ মেম্বার (যেমন `editor` বা `analyst`) সরাসরি API কল করে ফানেল ডিলিট, ফানেল আনপাবলিশ, টিম মেম্বারদের রোল পরিবর্তন বা ওয়েবহুক ডিলিট করতে সক্ষম ছিল।
* **সমাধান:**
  [ApiSpaController.php](file:///c:/xampp/htdocs/qfunnel2/app/Controllers/ApiSpaController.php) ও [AuthorizationService.php](file:///c:/xampp/htdocs/qfunnel2/app/Services/AuthorizationService.php)-এ নিম্নোক্ত পারমিশন এনফোর্সমেন্ট কার্যকর করা হয়েছে:
  - `deleteFunnel()`: `canDeleteFunnel()` দিয়ে শুধুমাত্র `owner` ও `admin` অনুমতি পাবে।
  - `publishFunnel()` ও `unpublishFunnel()`: শুধুমাত্র `owner` ও `admin` অনুমতি পাবে।
  - `duplicateFunnel()`: শুধুমাত্র `owner`, `admin`, `editor` অনুমতি পাবে।
  - `updateFunnelSettings()` ও `saveFunnelGraph()`: `canEditFunnel()` পারমিশন বাধ্যতামূলক।
  - `inviteTeamMember()` ও `updateTeamMember()`: শুধুমাত্র `owner` ও `admin` দল পরিচালনা করতে পারবে। কোনো সাধারণ সদস্য অন্য কাউকে বা নিজেকে `owner` করতে পারবে না।
  - `removeTeamMember()`: শুধুমাত্র `owner` ও `admin` সদস্য বাদ দিতে পারবে। **ওয়ার্কস্পেসের মূল `owner`-কে কোনো মেম্বার টিম থেকে রিমুভ করতে পারবে না।**
  - `exportSubmissions()`: সংবেদনশীল গ্রাহক লিড সিএসভি ডাউনলোড শুধুমাত্র `owner` ও `admin`-এর জন্য সংরক্ষিত।
  - `bulkSubmissionsAction(action: 'delete')`: বাল্ক লিড মুছে ফেলার জন্য `canManageWorkspace()` বাধ্যতামূলক।
  - `createWebhook()` ও `deleteWebhook()`: ওয়েবহুক তৈরি বা ডিলিট শুধুমাত্র `owner` ও `admin` করতে পারবে।
  - `createLeadStage()` ও `deleteLeadStage()`: লিড পাইপলাইন স্টেজ পরিবর্তন ওয়ার্কস্পেস ম্যানেজমেন্ট রোলের জন্য সুরক্ষিত।

---

### ৩.৬. ডিস্ট্রিবিউটেড এপিআই রেট লিমিটিং ইঞ্জিন (API Protection)
* **সমাধান:** নতুন [RateLimiterService.php](file:///c:/xampp/htdocs/qfunnel2/app/Services/RateLimiterService.php) সার্ভিস যুক্ত করে পুরো অ্যাপ্লিকেশনে একাধিক লেভেলে রিসোর্স সুরক্ষা কার্যকর করা হয়েছে:
  1. **External REST API (`/api/v1/*`):** প্রতি এপিআই কি-তে মিনিটে সর্বোচ্চ **৬০টি রিকোয়েস্ট** বরাদ্দ। অতিরিক্ত রিকোয়েস্টে স্ট্যান্ডার্ড HTTP 429 রেসপন্স দেওয়া হয়।
  2. **API অথেনটিকেশন ব্রুট-ফোর্স রোধ:** ভুল বা অজানা কি দিয়ে প্রতি আইপিতে মিনিটে সর্বোচ্চ **৩০টি রিকোয়েস্ট** সীমাবদ্ধ।
  3. **অ্যাকাউন্ট রেজিস্ট্রেশন (`/api/auth/register`):** একই আইপি থেকে ঘণ্টায় সর্বোচ্চ **৫টি অ্যাকাউন্ট** তৈরি করা যাবে (বট স্প্যাম ও স্টোরেজ অপচয় বন্ধ)।
  4. **পাবলিক সাপোর্ট টিকিট (`/api/public/support-tickets`):** একই আইপি থেকে ১০ মিনিটে সর্বোচ্চ **৫টি টিকিট** সাবমিট করা যাবে।
  5. **পাবলিক কুইজ রানার (`/api/quiz/{slug}/init`):** একই আইপি থেকে মিনিটে সর্বোচ্চ **৬০টি কুইজ সেশন** শুরু করা যাবে (সেশন স্পুফিং ও ডাটাবেজ ফ্লাড রোধ)।

---

### ৩.৭. CSV / Excel ফর্মুলা ইনজেকশন প্রতিরোধ (CWE-1236)
* **সমস্যা:** ফানেল থেকে লিড ডাটা সিএসভি এক্সপোর্ট করার সময় কোনো ভিজিটর নামের শুরুতে `=`, `+`, `-`, `@` বা কমান্ড ইনপুট দিলে এক্সেল বা গুগল শিটসে ওপেন করার সময় তা ক্ষতিকর ফর্মুলা হিসেবে এক্সিকিউট হওয়ার ঝুঁকি ছিল।
* **সমাধান:** [SubmissionService.php](file:///c:/xampp/htdocs/qfunnel2/app/Services/SubmissionService.php)-এ এক্সপোর্ট প্রক্রিয়ায় স্বয়ংক্রিয় ফিল্টার যুক্ত করা হয়েছে। যেকোনো ফিল্ডের প্রথম ক্যারেক্টার `=`, `+`, `-`, `@`, `\t` হলে তার পূর্বে স্বয়ংক্রিয়ভাবে একটি অ্যাপোস্ট্রফি (`'`) যুক্ত করে দেওয়া হয়।

---

### ৩.৮. ব্যাকগ্রাউন্ড ক্রন জব ও অটোমেশন হার্ডেনিং
* **সমস্যা:** `/api/cron/billing` এন্ডপয়েন্টে হার্ডকোডেড ডিফল্ট সিক্রেট কি ছিল এবং সাধারণ `!==` স্ট্রিং তুলনা ব্যবহারের কারণে সাইড-চ্যানেল টাইমিং অ্যাটাক ঝুঁকি ছিল।
* **সমাধান:** [ApiSpaController.php](file:///c:/xampp/htdocs/qfunnel2/app/Controllers/ApiSpaController.php)-এ ক্রিপ্টোগ্রাফিক কনস্ট্যান্ট-টাইম `hash_equals()` ফাংশন ব্যবহার করা হয়েছে। ইউআরএলের পাশাপাশি নিরাপদ `Authorization: Bearer <key>` এবং `X-Cron-Key` হেডার সাপোর্ট যুক্ত করা হয়েছে।

---

### ৩.৯. সার্ভার-সাইড রিকোয়েস্ট ফোরজারি (SSRF) ডিফেন্স
* **সমাধান:** [WebhookService.php](file:///c:/xampp/htdocs/qfunnel2/app/Services/WebhookService.php)-এ বহির্গামী ওয়েব পে-লোড ডিসপ্যাচের পূর্বে ডিএনএস রেজোলিউশন ও আইপি ফিল্টারিং জোরদার করা হয়েছে। `APP_ENV !== 'development'` হলে ক্লাউড মেটাডাটা ও লোকাল নেটওয়ার্কের সমস্ত আইপি এড্রেস (`127.0.0.1`, `169.254.169.254`, `10.0.0.0/8`, `192.168.0.0/16`, `::1`) স্বয়ংক্রিয়ভাবে বাতিল করা হয়।

---

## ৪. সংশোধিত ও তৈরিকৃত ফাইলসমূহের তালিকা (Audit File Manifest)

| ফাইল পাথ | ভূমিকা ও পরিবর্তনের বিবরণ |
| :--- | :--- |
| [public/uploads/.htaccess](file:///c:/xampp/htdocs/qfunnel2/public/uploads/.htaccess) | **(New)** অ্যাপাচি সার্ভারে আপলোড ফোল্ডারের স্ক্রিপ্ট এক্সিকিউশন ও ডিরেক্টরি ব্রাউজিং রোধক। |
| [app/Services/RateLimiterService.php](file:///c:/xampp/htdocs/qfunnel2/app/Services/RateLimiterService.php) | **(New)** স্লাইডিং উইন্ডো রেট লিমিটিং ইঞ্জিন (রেজিস্ট্রেশন, সাপোর্ট, কুইজ ও রেস্ট এপিআই)। |
| [database/migrations/016_login_rate_limiting.sql](file:///c:/xampp/htdocs/qfunnel2/database/migrations/016_login_rate_limiting.sql) | **(New)** রেট লিমিটিং ও লগইন প্রচেষ্টা ট্র্যাকিংয়ের ইনডেক্সড ডাটাবেজ টেবিল। |
| [public/index.php](file:///c:/xampp/htdocs/qfunnel2/public/index.php) | কঠোর CORS হোস্ট হোয়াইটলিস্ট এবং গুরুত্বপূর্ণ HTTP সিকিউরিটি হেডার্স প্রয়োগ। |
| [app/Controllers/ApiSpaController.php](file:///c:/xampp/htdocs/qfunnel2/app/Controllers/ApiSpaController.php) | ১৪+ টি এন্ডপয়েন্টে RBAC এনফোর্সমেন্ট, নিরাপদ MIME এক্সটেনশন, SVG স্যানিটাইজার ও রেট লিমিটিং। |
| [app/Controllers/ApiController.php](file:///c:/xampp/htdocs/qfunnel2/app/Controllers/ApiController.php) | বহির্গামী v1 REST API-তে প্রতি এপিআই কি ও আইপি ভিত্তিক রিকোয়েস্ট থ্রটলিং প্রয়োগ। |
| [app/Services/AuthService.php](file:///c:/xampp/htdocs/qfunnel2/app/Services/AuthService.php) | লগইনে ৫-প্রচেষ্টার ব্রুট-ফোর্স লকআউট এবং সেশন রি-জেনারেশন মেকানিজম। |
| [app/Services/AuthorizationService.php](file:///c:/xampp/htdocs/qfunnel2/app/Services/AuthorizationService.php) | ওয়ার্কস্পেস ও টিম ম্যানেজমেন্ট রোলের সুনির্দিষ্ট সংজ্ঞা ও পারমিশন লজিক। |
| [app/Services/SubmissionService.php](file:///c:/xampp/htdocs/qfunnel2/app/Services/SubmissionService.php) | CWE-1236 সিএসভি/এক্সেল ফর্মুলা ইনজেকশন প্রতিরোধক সেল স্যানিটাইজেশন। |
| [app/Services/WebhookService.php](file:///c:/xampp/htdocs/qfunnel2/app/Services/WebhookService.php) | ডিফল্ট নিরাপদ SSRF প্রোটেকশন ও রিজার্ভড নেটওয়ার্ক ব্লকিং। |
| [app/Middleware/CsrfMiddleware.php](file:///c:/xampp/htdocs/qfunnel2/app/Middleware/CsrfMiddleware.php) | হেডার ও বডি থেকে ক্রস-সাইট টোকেন নিষ্কাশন ও `hash_equals` ভ্যালিডেশন। |
| [routes/web.php](file:///c:/xampp/htdocs/qfunnel2/routes/web.php) | সমস্ত অভ্যন্তরীণ API রাউটে CSRF মিডলওয়্যার সক্রিয়করণ। |
| [database/cpanel_production_database.sql](file:///c:/xampp/htdocs/qfunnel2/database/cpanel_production_database.sql) | প্রোডাকশন ডাটাবেজ ডাম্পে নতুন টেবিল ও মাইগ্রেশন হিস্ট্রি আপডেট। |
| [production/qfunnel_production.zip](file:///c:/xampp/htdocs/qfunnel2/production/qfunnel_production.zip) | সমস্ত সিকিউরিটি প্যাচ ও ফ্রন্টএন্ড বিল্ড সহ সর্বশেষ প্রোডাকশন প্যাকেজ। |

---

## ৫. প্রোডাকশন ডেপ্লয়মেন্ট ও বেস্ট প্র্যাকটিস গাইডলাইন

1. **HTTPS / SSL এনফোর্সমেন্ট:** cPanel বা প্রোডাকশন হোস্টিংয়ে সবসময় HTTPS সক্রিয় রাখুন, যাতে ট্রানজিটের সময় কুকি এবং হেডার এনক্রিপ্টেড থাকে।
2. **Environment File Protection:** নিশ্চিত করুন সার্ভারে `.env` ফাইলটি ওয়েব থেকে সরাসরি ডাউনলোডযোগ্য নয় (রুট লেভেলের `.htaccess`-এ এটি সুরক্ষিত রয়েছে)।
3. **ক্রন সিক্রেট কি পরিবর্তন:** প্রোডাকশনে আপনার নিজস্ব গোপন `CRON_SECRET` ভ্যালু `.env` ফাইলে সেট করুন এবং cPanel ক্রন জবে হেডার বা প্যারামিটার হিসেবে ব্যবহার করুন।
4. **নিয়মিত ডাটাবেজ ব্যাকআপ:** গুরুত্বপূর্ণ গ্রাহক ও ট্রানজেকশন ডাটা সুরক্ষায় নিয়মিত স্বয়ংক্রিয় ব্যাকআপ ব্যবস্থা চালু রাখুন।

---

> **উপসংহার:**  
> এই নিরাপত্তা অডিটের মাধ্যমে অ্যাপ্লিকেশনটির ফ্রন্টএন্ড এবং ব্যাকএন্ড উভয় স্তরে এন্টারপ্রাইজ গ্রেড প্রতিরক্ষা নিশ্চিত করা হয়েছে। কোডবেস বর্তমানে নিরাপদ, স্থিতিশীল এবং যেকোনো আধুনিক ওয়েবহোস্টিং বা cPanel পরিবেশে ডিপ্লয় করার জন্য প্রস্তুত।
