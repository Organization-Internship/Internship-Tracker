# Internship & Project Tracker (v2)

This version adds the fields you requested:

## Internships (manual + AI)
- Title, Description
- **Stipend**
- **Duration**
- **Skills required**
- (No location; internships are online)

## Projects
- Title, Tech Stack
- **Start date, End date**
- **Status** (in-progress/completed)
- File upload (stored under `/uploads/projects`)
- Project link

## Profiles
- **Student**: phone, year, branch, LinkedIn, GitHub, resume PDF
- **Faculty**: department, contact info
- **Company**: company name, website, contact info

### Setup
1. Put folder in `C:\xampp\htdocs\internship_tracker`
2. Import DB: run `php/db.sql`
3. Open `http://localhost/internship_tracker/public/login.html`

### AI Internships
- Endpoint: `php/internships/generate_ai.php`
- Uses `OPENROUTER_API_KEY` if set; otherwise creates a mock AI internship.
- No location field is used or generated.

### Demo Logins
- student@example.com / password123
- faculty@example.com / password123
- company@example.com / password123
