/**
 * TaskFlow - Frontend Application Logic
 * Pure Vanilla JavaScript communicating with PHP RESTful API
 */

// Application State
const state = {
  tasks: [],
  stats: { total: 0, completed: 0, in_progress: 0, pending: 0 },
  filters: {
    status: '',
    priority: '',
    category: '',
    search: '',
    sort_by: 'created_at',
    order: 'DESC'
  },
  editingTaskId: null,
  deletingTaskId: null
};

// DOM Elements Cache
const DOM = {
  tasksContainer: document.getElementById('tasksContainer'),
  statTotal: document.getElementById('statTotal'),
  statCompleted: document.getElementById('statCompleted'),
  statInProgress: document.getElementById('statInProgress'),
  statPending: document.getElementById('statPending'),
  searchInput: document.getElementById('searchInput'),
  priorityFilter: document.getElementById('priorityFilter'),
  sortBySelect: document.getElementById('sortBySelect'),
  statusTabs: document.querySelectorAll('.tab-btn'),
  taskModal: document.getElementById('taskModal'),
  taskForm: document.getElementById('taskForm'),
  modalTitle: document.getElementById('modalTitle'),
  deleteModal: document.getElementById('deleteModal'),
  deleteTaskTitle: document.getElementById('deleteTaskTitle'),
  btnConfirmDelete: document.getElementById('btnConfirmDelete'),
  themeToggleBtn: document.getElementById('themeToggleBtn'),
  toastContainer: document.getElementById('toastContainer')
};

// ----------------------------------------------------
// Initialization
// ----------------------------------------------------
document.addEventListener('DOMContentLoaded', () => {
  initTheme();
  setupEventListeners();
  loadData();
});

function setupEventListeners() {
  // Theme Toggle
  if (DOM.themeToggleBtn) {
    DOM.themeToggleBtn.addEventListener('click', toggleTheme);
  }

  // Live Search with Debounce
  let searchTimeout;
  if (DOM.searchInput) {
    DOM.searchInput.addEventListener('input', (e) => {
      clearTimeout(searchTimeout);
      searchTimeout = setTimeout(() => {
        state.filters.search = e.target.value.trim();
        loadTasks();
      }, 300);
    });
  }

  // Priority Filter
  if (DOM.priorityFilter) {
    DOM.priorityFilter.addEventListener('change', (e) => {
      state.filters.priority = e.target.value;
      loadTasks();
    });
  }

  // Sort Select
  if (DOM.sortBySelect) {
    DOM.sortBySelect.addEventListener('change', (e) => {
      const [sortBy, order] = e.target.value.split(':');
      state.filters.sort_by = sortBy;
      state.filters.order = order;
      loadTasks();
    });
  }

  // Status Tabs
  DOM.statusTabs.forEach(tab => {
    tab.addEventListener('click', () => {
      DOM.statusTabs.forEach(t => t.classList.remove('active'));
      tab.classList.add('active');
      state.filters.status = tab.dataset.status || '';
      loadTasks();
    });
  });

  // Task Form Submit
  if (DOM.taskForm) {
    DOM.taskForm.addEventListener('submit', handleFormSubmit);
  }

  // Delete Confirm Button
  if (DOM.btnConfirmDelete) {
    DOM.btnConfirmDelete.addEventListener('click', executeDelete);
  }

  // Close modals on escape key or clicking backdrop
  window.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
      closeTaskModal();
      closeDeleteModal();
    }
  });

  [DOM.taskModal, DOM.deleteModal].forEach(modal => {
    if (modal) {
      modal.addEventListener('click', (e) => {
        if (e.target === modal) {
          closeTaskModal();
          closeDeleteModal();
        }
      });
    }
  });
}

// ----------------------------------------------------
// API Calls & Data Fetching
// ----------------------------------------------------
async function loadData() {
  await Promise.all([loadTasks(), loadStats()]);
}

async function loadStats() {
  try {
    const res = await fetch('api/tasks.php?action=stats');
    const result = await res.json();
    if (result.success && result.data) {
      state.stats = result.data;
      renderStats();
    }
  } catch (err) {
    console.error('Failed to load stats:', err);
  }
}

async function loadTasks() {
  try {
    const queryParams = new URLSearchParams();
    if (state.filters.status) queryParams.set('status', state.filters.status);
    if (state.filters.priority) queryParams.set('priority', state.filters.priority);
    if (state.filters.category) queryParams.set('category', state.filters.category);
    if (state.filters.search) queryParams.set('search', state.filters.search);
    if (state.filters.sort_by) queryParams.set('sort_by', state.filters.sort_by);
    if (state.filters.order) queryParams.set('order', state.filters.order);

    const res = await fetch(`api/tasks.php?${queryParams.toString()}`);
    const result = await res.json();

    if (result.success) {
      state.tasks = result.data.tasks || [];
      renderTasks();
    } else {
      showToast(result.message || 'Error fetching tasks', 'error');
    }
  } catch (err) {
    console.error('API Error:', err);
    showToast('Failed to connect to the backend API', 'error');
  }
}

// ----------------------------------------------------
// Render Methods
// ----------------------------------------------------
function renderStats() {
  if (DOM.statTotal) DOM.statTotal.textContent = state.stats.total;
  if (DOM.statCompleted) DOM.statCompleted.textContent = state.stats.completed;
  if (DOM.statInProgress) DOM.statInProgress.textContent = state.stats.in_progress;
  if (DOM.statPending) DOM.statPending.textContent = state.stats.pending;
}

function renderTasks() {
  if (!DOM.tasksContainer) return;

  if (state.tasks.length === 0) {
    DOM.tasksContainer.innerHTML = `
      <div class="empty-state">
        <div class="empty-icon">📝</div>
        <h3>No tasks found</h3>
        <p>There are no tasks matching your current filters. Add a new task to get started!</p>
        <button class="btn btn-primary" onclick="openCreateModal()">
          <span>+</span> Create New Task
        </button>
      </div>
    `;
    return;
  }

  DOM.tasksContainer.innerHTML = state.tasks.map(task => {
    const isDone = task.status === 'completed';
    const dueDateFormatted = task.due_date ? formatDate(task.due_date) : 'No deadline';
    const isOverdue = task.due_date && !isDone && new Date(task.due_date) < new Date().setHours(0, 0, 0, 0);

    return `
      <div class="task-card ${isDone ? 'is-completed' : ''}" data-id="${task.id}">
        <div>
          <div class="task-header">
            <div class="task-checkbox-container">
              <input 
                type="checkbox" 
                class="custom-checkbox" 
                title="${isDone ? 'Mark as pending' : 'Mark as completed'}"
                ${isDone ? 'checked' : ''} 
                onchange="toggleTaskStatus(${task.id}, this.checked)"
              />
            </div>
            <div class="task-title-area">
              <h3 class="task-title">${escapeHTML(task.title)}</h3>
            </div>
          </div>

          <div class="task-badges">
            <span class="badge badge-priority-${task.priority}">
              ● ${escapeHTML(task.priority)} priority
            </span>
            <span class="badge badge-status-${task.status}">
              ${formatStatusLabel(task.status)}
            </span>
            ${task.category ? `<span class="badge badge-category">📁 ${escapeHTML(task.category)}</span>` : ''}
          </div>

          ${task.description ? `<p class="task-description">${escapeHTML(task.description)}</p>` : ''}
        </div>

        <div class="task-footer">
          <div class="task-meta ${isOverdue ? 'overdue' : ''}">
            <span>📅</span>
            <span>${dueDateFormatted} ${isOverdue ? '(Overdue)' : ''}</span>
          </div>

          <div class="task-actions">
            <button class="btn-card-action" onclick="openEditModal(${task.id})" title="Edit Task">
              <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
              </svg>
            </button>
            <button class="btn-card-action delete" onclick="confirmDelete(${task.id})" title="Delete Task">
              <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="3 6 5 6 21 6"></polyline>
                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                <line x1="10" y1="11" x2="10" y2="17"></line>
                <line x1="14" y1="11" x2="14" y2="17"></line>
              </svg>
            </button>
          </div>
        </div>
      </div>
    `;
  }).join('');
}

// ----------------------------------------------------
// Form Handling (Create & Update)
// ----------------------------------------------------
function openCreateModal() {
  state.editingTaskId = null;
  DOM.modalTitle.textContent = 'Create New Task';
  DOM.taskForm.reset();
  document.getElementById('taskPriority').value = 'medium';
  document.getElementById('taskStatus').value = 'pending';
  document.getElementById('taskCategory').value = 'Work';
  DOM.taskModal.classList.add('active');
  document.getElementById('taskTitle').focus();
}

function openEditModal(id) {
  const task = state.tasks.find(t => t.id == id);
  if (!task) return;

  state.editingTaskId = id;
  DOM.modalTitle.textContent = 'Edit Task';
  
  document.getElementById('taskTitle').value = task.title || '';
  document.getElementById('taskDescription').value = task.description || '';
  document.getElementById('taskCategory').value = task.category || 'General';
  document.getElementById('taskPriority').value = task.priority || 'medium';
  document.getElementById('taskStatus').value = task.status || 'pending';
  document.getElementById('taskDueDate').value = task.due_date || '';

  DOM.taskModal.classList.add('active');
  document.getElementById('taskTitle').focus();
}

function closeTaskModal() {
  if (DOM.taskModal) DOM.taskModal.classList.remove('active');
  state.editingTaskId = null;
}

async function handleFormSubmit(e) {
  e.preventDefault();

  const title = document.getElementById('taskTitle').value.trim();
  const description = document.getElementById('taskDescription').value.trim();
  const category = document.getElementById('taskCategory').value.trim();
  const priority = document.getElementById('taskPriority').value;
  const status = document.getElementById('taskStatus').value;
  const dueDate = document.getElementById('taskDueDate').value;

  if (!title) {
    showToast('Task title is required', 'error');
    return;
  }

  const payload = {
    title,
    description,
    category,
    priority,
    status,
    due_date: dueDate || null
  };

  const isEditing = state.editingTaskId !== null;
  const url = isEditing ? `api/tasks.php?id=${state.editingTaskId}` : 'api/tasks.php';
  const method = isEditing ? 'PUT' : 'POST';

  try {
    const res = await fetch(url, {
      method: method,
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    });

    const result = await res.json();

    if (result.success) {
      showToast(isEditing ? 'Task updated successfully!' : 'Task created successfully!', 'success');
      closeTaskModal();
      await loadData();
    } else {
      showToast(result.message || 'Operation failed', 'error');
    }
  } catch (err) {
    console.error('Error saving task:', err);
    showToast('Network error while saving task', 'error');
  }
}

// ----------------------------------------------------
// Quick Status Toggle
// ----------------------------------------------------
async function toggleTaskStatus(id, isChecked) {
  const newStatus = isChecked ? 'completed' : 'pending';

  try {
    const res = await fetch(`api/tasks.php?id=${id}`, {
      method: 'PATCH',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ status: newStatus })
    });

    const result = await res.json();

    if (result.success) {
      showToast(`Task marked as ${newStatus}`, 'info');
      await loadData();
    } else {
      showToast(result.message || 'Failed to update status', 'error');
      await loadTasks(); // Revert UI
    }
  } catch (err) {
    console.error('Error toggling status:', err);
    showToast('Network error toggling status', 'error');
    await loadTasks();
  }
}

// ----------------------------------------------------
// Deletion Handling
// ----------------------------------------------------
function confirmDelete(id) {
  const task = state.tasks.find(t => t.id == id);
  if (!task) return;

  state.deletingTaskId = id;
  if (DOM.deleteTaskTitle) {
    DOM.deleteTaskTitle.textContent = `"${task.title}"`;
  }
  DOM.deleteModal.classList.add('active');
}

function closeDeleteModal() {
  if (DOM.deleteModal) DOM.deleteModal.classList.remove('active');
  state.deletingTaskId = null;
}

async function executeDelete() {
  if (!state.deletingTaskId) return;

  try {
    const res = await fetch(`api/tasks.php?id=${state.deletingTaskId}`, {
      method: 'DELETE'
    });

    const result = await res.json();

    if (result.success) {
      showToast('Task deleted successfully', 'success');
      closeDeleteModal();
      await loadData();
    } else {
      showToast(result.message || 'Failed to delete task', 'error');
    }
  } catch (err) {
    console.error('Error deleting task:', err);
    showToast('Network error while deleting', 'error');
  }
}

// ----------------------------------------------------
// Toast Notification Utility
// ----------------------------------------------------
function showToast(message, type = 'info') {
  if (!DOM.toastContainer) return;

  const toast = document.createElement('div');
  toast.className = `toast ${type}`;

  const icons = {
    success: '✓',
    error: '✕',
    info: 'ℹ'
  };

  toast.innerHTML = `
    <span style="font-weight: bold; font-size: 1.1rem;">${icons[type] || 'ℹ'}</span>
    <span>${escapeHTML(message)}</span>
  `;

  DOM.toastContainer.appendChild(toast);

  setTimeout(() => {
    toast.style.opacity = '0';
    toast.style.transform = 'translateX(100%)';
    toast.style.transition = 'all 0.3s ease-out';
    setTimeout(() => toast.remove(), 300);
  }, 3500);
}

// ----------------------------------------------------
// Theme Management (Dark / Light)
// ----------------------------------------------------
function initTheme() {
  const savedTheme = localStorage.getItem('taskflow_theme') || 'dark';
  document.documentElement.setAttribute('data-theme', savedTheme);
  updateThemeIcon(savedTheme);
}

function toggleTheme() {
  const currentTheme = document.documentElement.getAttribute('data-theme') || 'dark';
  const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
  document.documentElement.setAttribute('data-theme', newTheme);
  localStorage.setItem('taskflow_theme', newTheme);
  updateThemeIcon(newTheme);
}

function updateThemeIcon(theme) {
  if (!DOM.themeToggleBtn) return;
  DOM.themeToggleBtn.innerHTML = theme === 'dark' 
    ? `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>` 
    : `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>`;
}

// ----------------------------------------------------
// Helpers
// ----------------------------------------------------
function escapeHTML(str) {
  if (!str) return '';
  return String(str)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}

function formatDate(dateStr) {
  if (!dateStr) return '';
  const d = new Date(dateStr);
  if (isNaN(d.getTime())) return dateStr;
  return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
}

function formatStatusLabel(status) {
  switch (status) {
    case 'completed': return 'Completed';
    case 'in_progress': return 'In Progress';
    case 'pending': return 'Pending';
    default: return status;
  }
}
