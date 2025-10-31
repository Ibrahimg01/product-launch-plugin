# V3 Validation Engine - Quick Start (5 Minutes)

## Prerequisites

- WordPress multisite or single site
- Product Launch plugin installed
- OpenAI API key

---

## Step 1: Configure OpenAI (Required)

1. Get API key from https://platform.openai.com/api-keys
2. In WordPress admin: **Product Launch > Settings**
3. Paste key into **OpenAI API Key** field
4. Click **Save Settings**

✅ **Test:** Submit a validation - should complete successfully (even without other APIs).

---

## Step 2: Configure SerpAPI (Recommended)

1. Create account at https://serpapi.com
2. Get API key from dashboard
3. In WordPress: **Network Admin > Settings > Validation APIs**
4. Paste into **SerpAPI Key** field
5. Click **Save API Keys**

✅ **Test:** API status should show green checkmark.

---

## Step 3: Configure Reddit (Optional)

1. Go to https://www.reddit.com/prefs/apps
2. Click **create app** (type: "script")
3. Copy **client ID** (under app name) and **secret**
4. In WordPress: **Network Admin > Settings > Validation APIs**
5. Paste both values
6. Click **Save API Keys**

✅ **Test:** Status indicator shows green.

---

## Step 4: Run Your First Validation

1. Navigate to **Ideas Library**
2. Click **Validate New Idea**
3. Enter: *"AI-powered scheduling tool for freelancers"*
4. Click **Submit**
5. Wait ~20 seconds

**Expected result:** 
- Overall score (0-100)
- 6 signal cards with individual scores
- Confidence badge
- Recommended actions list

---

## Step 5: Push to 8-Phase System

1. In the validation report, click **Push to 8-Phase Launch System**
2. Navigate to **Product Launch > Market Clarity**
3. **Verify:** Fields are pre-filled with validation data

✅ **Success!** You can now modify/expand the auto-filled content.

---

## Common Issues

**"Validation Failed" error:**
- Check OpenAI key is correct
- Verify server can reach api.openai.com

**Scores seem off:**
- Configure SerpAPI for accurate market data
- Check confidence level (low = unreliable)

**Slow validations (>60s):**
- Network timeout issues
- Try again (may have been API rate limit)

---

## Next Steps

- Read full user guide: `docs/V3-VALIDATION-GUIDE.md`
- Explore signal breakdown in validation reports
- Publish validated ideas to Ideas Library
- Configure quota limits per user (optional)

---

## Support

- Check status: **Network Settings > Validation APIs**
- View logs: `wp-content/debug.log` (if `WP_DEBUG_LOG` enabled)
- Need help? Contact plugin support

---

*Setup time: ~5 minutes | Cost: ~$0.05-0.07 per validation*

