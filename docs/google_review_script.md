# Google App Review: Video Recording Script & Verification Guide

This document defines the step-by-step recording script for the **Google OAuth App Verification Video**. It strictly complies with Google Trust & Safety verification guidelines for read-only scopes.

---

## Technical Reviewer Checkpoints (Pre-Recording Checklist)

> [!IMPORTANT]
> **Must-Have Requirements for Video Approval:**
> 1. **Target Scopes:**
>    - `https://www.googleapis.com/auth/analytics.readonly`
>    - `https://www.googleapis.com/auth/webmasters.readonly`
> 2. **Client ID Visibility:** The browser address bar MUST be clearly visible during the Google OAuth consent step, showing the `client_id` parameter (e.g., `client_id=...apps.googleusercontent.com`).
> 3. **English Interface:** Both the Google OAuth consent screen and the APIs Hub UI MUST be displayed in **English**.
> 4. **Pre-Authenticated State:** Start with an existing, logged-in session. Do NOT show registration, signup forms, email verification, or unrelated third-party integrations (e.g., Meta, Stripe).
> 5. **Read-Only Compliance:** Demonstrate read-only visualization only. Do NOT perform create, edit, or delete operations.
> 6. **Duration:** Video run-time must be between **60 and 150 seconds (1:00 – 2:30)** maximum.

---

## Video Script Flow

### **Scene 1: App Context & Entry Point**
- **Duration:** 10–15 seconds
- **Visual Cues:**
  - Start inside an already authenticated APIs Hub project workspace (e.g., `https://dev.apis-hub.cloud/app/demo-project`).
  - Navigate to **Integrations** > **Data Sources** (`/app/demo-project/data-sources`).
  - Highlight the Google provider section with the **"Connect Account"** or **"Update Permissions"** action button.
- **Audio Cues / Voiceover (or On-Screen Captions):**
  > "Welcome to APIs Hub. Our platform allows marketing and analytics teams to aggregate their web traffic and search performance data into unified, read-only reporting dashboards. Here inside our Data Sources settings, I will demonstrate how users connect their Google account to fetch Search Console and Analytics data."
- **Reviewer Verification Point:** Confirms pre-authenticated application state, English UI, and clear entry point.

---

### **Scene 2: Google OAuth Consent Flow [CRITICAL]**
- **Duration:** 20–30 seconds
- **Visual Cues:**
  - Click the **"Connect Account"** / **"Update Permissions"** button. Select Google Search Console and Google Analytics channels if prompted.
  - The browser redirects to `https://accounts.google.com/o/oauth2/v2/auth`.
  - **ZOOM / HIGHLIGHT:** Ensure the browser address bar is fully visible, highlighting `client_id=...apps.googleusercontent.com` in the URL.
  - **CHECK:** Confirm the Google OAuth screen is rendered in **English**.
  - Show the permissions explicitly listed on screen:
    - *See and download your Google Analytics data* (`analytics.readonly`)
    - *View Search Console data for your verified sites* (`webmasters.readonly`)
  - Select the Google Account, check all requested permission checkboxes, and click **Continue**.
- **Audio Cues / Voiceover (or On-Screen Captions):**
  > "Clicking Connect opens the Google OAuth consent screen in English. Notice in the browser address bar our verified OAuth Client ID. We explicitly request two read-only scopes: `analytics.readonly` to access Google Analytics 4 data, and `webmasters.readonly` to view Search Console performance. I select the account, grant the requested read-only permissions, and complete authentication."
- **Reviewer Verification Point:** Address bar shows full `client_id`, consent screen is in English, exact `readonly` scopes match the verification request.

---

### **Scene 3: In-App Data Ingestion & Visualization**
- **Duration:** 30–45 seconds
- **Visual Cues:**
  - Redirect seamlessly back to APIs Hub (**Data Sources** page showing connection status as *Active*).
  - Navigate to **Analytics** > **Dashboards** (`/app/demo-project/dashboards`).
  - Open the **Google Search Console** dashboard:
    - Select a verified Search Console property from the property dropdown.
    - Point to widgets displaying **Impressions**, **Clicks**, **CTR (Click-Through Rate)**, **Average Position**, and top **Search Queries**.
  - Open the **Google Analytics 4** dashboard:
    - Select a verified GA4 property from the property dropdown.
    - Point to widgets displaying **Sessions**, **Active Users**, **Pageviews**, and **Engagement Rate**.
- **Audio Cues / Voiceover (or On-Screen Captions):**
  > "Upon successful authentication, the user is redirected back to APIs Hub. In our Search Console dashboard, we select a verified property to render read-only performance metrics: search queries, total impressions, clicks, CTR, and position. Next, in our Google Analytics dashboard, we select a GA4 property to view sessions, active users, pageviews, and engagement metrics. All data is processed strictly in a read-only capacity for dashboard reporting. Thank you for reviewing APIs Hub."
- **Reviewer Verification Point:** Demonstrates exact UI usage of `analytics.readonly` and `webmasters.readonly` data; read-only scope justification verified.

---

## Summary of Video Metadata

| Parameter | Specification |
| :--- | :--- |
| **Total Duration** | ~60 to 90 seconds (Target: 1 min 15 sec) |
| **Language** | English (UI & Voiceover/Captions) |
| **Client ID** | Visible in browser address bar during OAuth redirect |
| **Target Scopes** | `analytics.readonly`, `webmasters.readonly` |
| **Access Level** | Read-Only (no create/update/delete operations) |
