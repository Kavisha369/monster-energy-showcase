# Monster Energy Showcase

Monster Energy Showcase is a premium, interactive product showcase website featuring data-driven SVG gauges, GSAP animations, and a secure PHP administration panel.

### ⚡ Features
* **Dynamic Gauges**: SVG radial circles that animate dynamically using spring eases based on product specs.
* **Relational Database**: Normalized benefits schema mapping ingredient tags using many-to-many junction tables.
* **API Robustness**: Structured JSON endpoints enforcing HTTP request method controls and clean error responses.
* **Security Hardening**: Session CSRF token protections, input XSS sanitizers, and parameterized SQL queries.

### 📷 Preview
<img src="https://github.com/user-attachments/assets/960a3732-26a4-4892-b3f8-ed1e127bd09c" width="800" />
<img  src="https://github.com/user-attachments/assets/fd94984c-b426-4ccf-b105-104e5535b7b9" width="800"/>
<img  src="https://github.com/user-attachments/assets/b4b90374-065e-40e4-b2c6-36962252eaa8" width="800" />
<img src="https://github.com/user-attachments/assets/2d3931f1-bd83-468f-8d9c-3c7380145912" width="800"/>
<img  src="https://github.com/user-attachments/assets/3447a86e-267d-4d80-aa88-773960426602" width="800"/>


### 🚀 Getting Started
1. **Clone**: Copy this repository into your XAMPP `htdocs` folder.
2. **Environment**: Create a `.env` file in the root and add your database configuration credentials.
3. **Database**: Open `/setup.php` in your browser to automatically build, index, and seed the database.
4. **Unleash**: View `/index.php` to access the live showcase, and `/admin.php` for the control dashboard.

### 🛠️ Tech Stack
* **Frontend**: HTML5, CSS3 (Premium Cinematic Design), GSAP animations.
* **Backend**: PHP 8+ (Dependency-free native structure).
* **Database**: MySQL (via XAMPP).
