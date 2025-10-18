# Phase 5 Setup Instructions

After implementing Phase 5, create the following pages in WordPress:

## 1. Ideas Library Page
- **Title:** "Business Ideas Library"
- **Slug:** `ideas-library`
- **Shortcode:** `[pl_ideas_library]`

## 2. Idea Details Page
- **Title:** "Idea Details"
- **Slug:** `idea-details`
- **Shortcode:** `[pl_idea_details]`

## 3. Update Existing Pages

### Validation Form Page
- Add link to library: "Browse pre-validated ideas"

### My Validations Page
- Add "Push to 8 Phases" button to each validation card

## URL Structure

- Library: `yourdomain.com/ideas-library/`
- Details: `yourdomain.com/idea-details/?idea_id=EXTERNAL_ID`
- Project: `yourdomain.com/project/?id=PROJECT_ID`

## Integration with 8-Phase System

The system will automatically:
1. Create a new project in `wp_pl_projects` table
2. Populate all 8 phases in `wp_pl_phase_data` table
3. Fill Phase 1 with market research data
4. Fill Phase 2 with product definition
5. Fill Phase 3 with branding suggestions
6. Fill Phase 4 with implementation strategies
7. Fill Phase 5 with SEO keywords
8. Fill Phase 6-8 with placeholder data

## Testing the Integration

1. Go to Ideas Library page
2. Browse and filter ideas
3. Click "View Details" on any idea
4. Click "Push to 8-Phase System" (must be logged in)
5. Verify project is created with pre-filled data
6. Navigate to project page to see populated phases

## API Configuration

Update settings in:
- WordPress Admin → Idea Validation → Settings
- Set APIFY API key (when available)
- Set API endpoint (when using APIFY)

## Scheduled Tasks

The system automatically syncs top ideas daily via WP-Cron:
- Task: `pl_sync_library_ideas`
- Frequency: Daily
- Cached for: 24 hours

## Database Tables Used

- `wp_pl_validations` - User validations
- `wp_pl_projects` - Launch projects
- `wp_pl_phase_data` - 8-phase data
- `wp_pl_validation_enrichment` - Cached enrichment data

## Testing Phase 5:
After Codex implements:

Activate/Re-activate Plugin:

This creates the new tables (wp_pl_projects, wp_pl_phase_data)

Create Pages:

Create "Ideas Library" page with [pl_ideas_library] shortcode
Create "Idea Details" page with [pl_idea_details] shortcode

Test Library:

Visit the library page
Test filtering and search
Test pagination
Click on an idea to view details

Test Push to 8 Phases:

Log in as a user
View an idea's details
Click "Push to 8-Phase System"
Verify project is created in database
Check that all 8 phases are populated

Test with User's Own Validation:

Create a validation from the form
Go to "My Validations"
Click "Push to 8 Phases" on your validation
Verify it creates a project
