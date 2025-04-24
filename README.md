# Salauddin and Surovi's Task Manager

A web-based task management application designed to help users organize tasks and enhance focus using an integrated Pomodoro timer and related techniques. This version leverages PHP and MySQL to persist task data for individual users based on a username setting.

![image](https://github.com/user-attachments/assets/57a2aa9a-ed85-4399-9abe-3f6a5f46b48f)

![image](https://github.com/user-attachments/assets/9a6db6ce-91ff-473c-95a9-c5983f08069e)

![image](https://github.com/user-attachments/assets/59dd1b0e-6216-4888-a25e-6f93921493ab)

*(Suggestion: Replace placeholder.png with an actual screenshot of the application)*

## Features

- ✔️ **Task Management:** Create, Read, Update, Delete (CRUD) tasks.
- ✨ **Task Prioritization:** Assign Low, Medium, or High priority to tasks.
- ✔️ **Filtering:** View All, Pending, or Completed tasks.
- 🤏 **Drag & Drop Reordering:** Easily rearrange pending tasks.
- ⏱️ **Focus Timer:**
  - Pomodoro Timer (configurable duration)
  - Short & Long Breaks (configurable durations)
  - Stopwatch Mode
  - Custom Timer Mode
  - Visual progress indicator circle
- 👤 **User-Specific Tasks:** Tasks are loaded and saved based on the username set in Settings (Note: No password protection in this version).
- 📊 **Basic Stats (Local Storage):**
  - Tasks Completed Today
  - Overall Task Progress (%)
  - Pomodoros Completed Today
  - Total Focus Time Today
  - Daily & Weekly Streaks (simple implementation)
  - Visual Pomodoro Batch tracking (🔴🟢🔵)
- ⚙️ **Settings Panel:**
  - Set Username (determines which tasks are loaded/saved)
  - Configure timer durations (Pomodoro, Short/Long Break)
  - Toggle UI/Alert Sounds
  - Select Background Ambience Sounds (None, Focus, Rain, Cafe)
  - Reset all local data and clear tasks for the current user
- 🎨 **Theme Toggle:** Switch between Light and Dark modes.
- 🔊 **Sound Effects:** Optional UI clicks, timer alerts, and background ambience.
- 💬 **Motivational Quotes:** Rotating quotes in the header.
- 📱 **Responsive Design:** Adapts to various screen sizes.

## Tech Stack

- **Frontend:** HTML5, CSS3, Vanilla JavaScript (ES6+)
- **Backend:** PHP (>= 7.4 recommended)
- **Database:** MySQL / MariaDB
- **Web Server:** Apache / Nginx (or similar with PHP support)

## Prerequisites

- A web server (e.g., Apache or Nginx) installed and running.
- PHP installed and configured with the web server (version 7.4 or higher recommended).
- MySQL or MariaDB database server installed and running.
- A database management tool like phpMyAdmin (optional, but helpful) or MySQL command-line access.
- A modern web browser.

## Installation & Setup

1. **Clone or Download:**
   - Clone the repository: `git clone <repository-url>`
   - OR Download the ZIP file and extract it.

2. **Database Setup:**
   - Access your MySQL server (e.g., via phpMyAdmin or command line).
   - Create a new database (e.g., `taskmanager_db`).
   - Select the created database and run the following SQL commands to create the necessary tables:

     ```sql
     -- Create users table
     CREATE TABLE users (
         user_id INT AUTO_INCREMENT PRIMARY KEY,
         username VARCHAR(100) NOT NULL UNIQUE,
         created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
     ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

     -- Create tasks table
     CREATE TABLE tasks (
         task_id INT AUTO_INCREMENT PRIMARY KEY,
         user_id INT NOT NULL,
         text TEXT NOT NULL,
         priority ENUM('low', 'medium', 'high') NOT NULL DEFAULT 'medium',
         completed BOOLEAN NOT NULL DEFAULT 0,
         created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
         completed_at TIMESTAMP NULL DEFAULT NULL,
         sort_order INT NOT NULL DEFAULT 0,
         FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
     ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

     -- Add index for faster task fetching per user
     CREATE INDEX idx_user_tasks ON tasks(user_id, completed, sort_order);
     ```

![image](https://github.com/user-attachments/assets/e14bf8d3-e94c-48b3-bf93-470048890fbe)

![image](https://github.com/user-attachments/assets/71d975b9-d7fd-473b-99e3-6cd61315e998)

3. **Configuration:**
   - Locate the `db_config.php` file and open it in a text editor.
   - Update the database credentials:
     - `DB_HOST`: Usually `localhost` or `127.0.0.1`.
     - `DB_USERNAME`: Your MySQL username (e.g., `root` for local setups; use a dedicated user in production).
     - `DB_PASSWORD`: Your MySQL password for the specified user.
     - `DB_NAME`: The name of the database you created (e.g., `taskmanager_db`).
   - Ensure the user has `SELECT`, `INSERT`, `UPDATE`, `DELETE` privileges on the `taskmanager_db` database.

4. **Deployment:**
   - Copy the project files (`taskmanager.html`, `api.php`, `db_config.php`) to your web server's document root (e.g., `htdocs` for XAMPP, `/var/www/html` for Apache/Nginx on Linux).
   - Ensure the web server has permissions to read these files and PHP can write to its error log.

5. **Access:**
   - Open your web browser and navigate to the project location (e.g., `http://localhost/taskmanager.html` or `http://localhost/your-project-folder/taskmanager.html`).

## Usage

1. **Set Username:** Go to Settings (⚙️ icon) and enter your desired username. This associates tasks with you. If the user doesn't exist, it will be created automatically. **Changing the username will load tasks for that new user.**
2. **Add Tasks:** Type a task description, select a priority, and click "Add Task".
3. **Manage Tasks:**
   - Mark tasks complete/incomplete using the checkbox.
   - Edit tasks by clicking the pencil (✏️) icon, making changes, and hitting Enter or clicking the save (💾) icon.
   - Delete tasks using the trash can (🗑️) icon (requires confirmation).
   - Drag and drop pending tasks to reorder them.
4. **Use Timer:**
   - Select a timer mode (Pomodoro, Breaks, Stopwatch, Custom).
   - Use the "Start"/"Pause" and "Reset" buttons.
   - Configure default durations in Settings.
5. **Filter Tasks:** Use the "All", "Pending", "Completed" buttons to filter the task list.
6. **Toggle Theme:** Click the Sun (☀️) / Moon (🌙) icon to switch between light and dark modes.
7. **Adjust Settings:** Use the Settings panel (⚙️) to change username, timer defaults, and sound preferences.

## Project Structure

```
├── taskmanager.html  # Main HTML file with CSS and JavaScript logic
├── api.php           # PHP backend API endpoint handling database interactions
└── db_config.php     # PHP database connection configuration (ignored by git if configured)
```

## Future Improvements / TODO

- **User Authentication:** Implement proper login/registration with password hashing.
- **Database Storage:** Move Settings and Stats from `localStorage` to the database.
- **Due Dates & Reminders:** Add optional due dates and browser notifications.
- **Task Categories/Tags:** Allow categorizing or tagging tasks.
- **Advanced Stats:** Detailed productivity reports and visualizations.
- **Error Handling:** Specific feedback on API or database errors.
- **Testing:** Add unit/integration tests for backend and frontend logic.
- **Refactor:** Separate CSS and JavaScript into dedicated files.

## License

This project is licensed under the Monozog.com License (to be confirmed).
