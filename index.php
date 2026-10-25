<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>TaskFlow — Full Stack CRUD Web Application</title>
  <meta name="description" content="A modern, high-performance Full Stack CRUD application built with native PHP, SQLite/MySQL, and modern Vanilla JavaScript.">

  <!-- Google Fonts: Outfit & Inter -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@500;600;700;800&display=swap" rel="stylesheet">

  <!-- Application Stylesheet -->
  <link rel="stylesheet" href="css/style.css">
</head>
<body>

  <div class="container">
    <!-- Header -->
    <header class="site-header">
      <div class="brand-wrapper">
        <div class="brand-icon" aria-hidden="true">✓</div>
        <div class="brand-info">
          <h1>TaskFlow</h1>
          <p>Full Stack CRUD Operations with PHP REST API</p>
        </div>
      </div>

      <div class="header-actions">
        <!-- Theme Toggle -->
        <button id="themeToggleBtn" class="btn btn-secondary btn-icon-only" title="Toggle Theme" aria-label="Toggle Dark and Light theme">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/>
          </svg>
        </button>

        <!-- New Task Button -->
        <button class="btn btn-primary" onclick="openCreateModal()" id="btnOpenCreate">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <line x1="12" y1="5" x2="12" y2="19"></line>
            <line x1="5" y1="12" x2="19" y2="12"></line>
          </svg>
          <span>New Task</span>
        </button>
      </div>
    </header>

    <!-- Metrics Section -->
    <section class="metrics-grid" aria-label="Task Statistics">
      <div class="metric-card total">
        <div class="metric-icon">📋</div>
        <div class="metric-content">
          <div class="metric-value" id="statTotal">0</div>
          <div class="metric-label">Total Tasks</div>
        </div>
      </div>

      <div class="metric-card completed">
        <div class="metric-icon">🎉</div>
        <div class="metric-content">
          <div class="metric-value" id="statCompleted">0</div>
          <div class="metric-label">Completed</div>
        </div>
      </div>

      <div class="metric-card in-progress">
        <div class="metric-icon">⚡</div>
        <div class="metric-content">
          <div class="metric-value" id="statInProgress">0</div>
          <div class="metric-label">In Progress</div>
        </div>
      </div>

      <div class="metric-card pending">
        <div class="metric-icon">⏳</div>
        <div class="metric-content">
          <div class="metric-value" id="statPending">0</div>
          <div class="metric-label">Pending</div>
        </div>
      </div>
    </section>

    <!-- Toolbar & Filters -->
    <section class="toolbar-section" aria-label="Filters and Controls">
      <div class="toolbar-top">
        <div class="search-box">
          <span class="search-icon" aria-hidden="true">🔍</span>
          <input 
            type="text" 
            id="searchInput" 
            class="search-input" 
            placeholder="Search tasks by title or details..." 
            aria-label="Search tasks"
          />
        </div>

        <div class="filter-selects">
          <select id="priorityFilter" class="custom-select" aria-label="Filter by priority">
            <option value="">All Priorities</option>
            <option value="high">High Priority</option>
            <option value="medium">Medium Priority</option>
            <option value="low">Low Priority</option>
          </select>

          <select id="sortBySelect" class="custom-select" aria-label="Sort tasks">
            <option value="created_at:DESC">Newest First</option>
            <option value="created_at:ASC">Oldest First</option>
            <option value="due_date:ASC">Due Date (Soonest)</option>
            <option value="title:ASC">Title (A-Z)</option>
          </select>
        </div>
      </div>

      <!-- Status Filter Tabs -->
      <nav class="status-tabs" aria-label="Filter by status">
        <button class="tab-btn active" data-status="">All Tasks</button>
        <button class="tab-btn" data-status="pending">Pending</button>
        <button class="tab-btn" data-status="in_progress">In Progress</button>
        <button class="tab-btn" data-status="completed">Completed</button>
      </nav>
    </section>

    <!-- Task List / Grid -->
    <main>
      <div id="tasksContainer" class="tasks-grid" aria-live="polite">
        <!-- Dynamic Tasks will be loaded here via JavaScript -->
      </div>
    </main>
  </div>

  <!-- Create / Edit Modal -->
  <div id="taskModal" class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
    <div class="modal-box">
      <div class="modal-header">
        <h2 id="modalTitle" class="modal-title">Create New Task</h2>
        <button type="button" class="modal-close-btn" onclick="closeTaskModal()" aria-label="Close modal">&times;</button>
      </div>

      <form id="taskForm">
        <div class="modal-body">
          <div class="form-group">
            <label for="taskTitle">Task Title <span class="required">*</span></label>
            <input 
              type="text" 
              id="taskTitle" 
              class="form-control" 
              placeholder="e.g., Implement Database Connection" 
              required 
              maxlength="255"
            />
          </div>

          <div class="form-row">
            <div class="form-group">
              <label for="taskCategory">Category</label>
              <input 
                type="text" 
                id="taskCategory" 
                class="form-control" 
                placeholder="e.g., Work, Study, Personal" 
                value="Work"
              />
            </div>

            <div class="form-group">
              <label for="taskPriority">Priority</label>
              <select id="taskPriority" class="form-control">
                <option value="low">Low Priority</option>
                <option value="medium" selected>Medium Priority</option>
                <option value="high">High Priority</option>
              </select>
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label for="taskStatus">Status</label>
              <select id="taskStatus" class="form-control">
                <option value="pending" selected>Pending</option>
                <option value="in_progress">In Progress</option>
                <option value="completed">Completed</option>
              </select>
            </div>

            <div class="form-group">
              <label for="taskDueDate">Due Date</label>
              <input type="date" id="taskDueDate" class="form-control" />
            </div>
          </div>

          <div class="form-group">
            <label for="taskDescription">Description</label>
            <textarea 
              id="taskDescription" 
              class="form-control" 
              rows="3" 
              placeholder="Add extra details, notes, or subtasks..."
            ></textarea>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" onclick="closeTaskModal()">Cancel</button>
          <button type="submit" class="btn btn-primary" id="btnSubmitTask">Save Task</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Delete Confirmation Modal -->
  <div id="deleteModal" class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="deleteModalTitle">
    <div class="modal-box" style="max-width: 440px;">
      <div class="modal-header">
        <h2 id="deleteModalTitle" class="modal-title" style="color: #f43f5e;">Confirm Deletion</h2>
        <button type="button" class="modal-close-btn" onclick="closeDeleteModal()" aria-label="Close modal">&times;</button>
      </div>
      <div class="modal-body">
        <p style="color: var(--text-muted); margin-bottom: 0.5rem;">
          Are you sure you want to permanently delete this task?
        </p>
        <p id="deleteTaskTitle" style="font-weight: 600; color: var(--text-main); word-break: break-word;"></p>
        <p style="font-size: 0.82rem; color: #f43f5e; margin-top: 0.75rem;">
          ⚠️ This action cannot be undone.
        </p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="closeDeleteModal()">Cancel</button>
        <button type="button" class="btn btn-danger" id="btnConfirmDelete">Delete Task</button>
      </div>
    </div>
  </div>

  <!-- Toast Notification Container -->
  <div id="toastContainer" class="toast-container" aria-live="assertive"></div>

  <!-- Application Logic -->
  <script src="js/app.js"></script>
</body>
</html>
