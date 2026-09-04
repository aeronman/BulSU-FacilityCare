/**
 * BulSU FacilityCare - Main JavaScript
 */

document.addEventListener('DOMContentLoaded', function() {
    initializeApp();
});

function initializeApp() {
    initNotificationClick();
    initFormValidation();
    initTooltip();
    initAutoRefresh();
}

function initNotificationClick() {
    const notificationLinks = document.querySelectorAll('[data-notification-id]');
    notificationLinks.forEach(link => {
        link.addEventListener('click', function() {
            const notificationId = this.getAttribute('data-notification-id');
            if (notificationId) {
                fetch('/api/notification/read', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: 'notification_id=' + notificationId
                });
            }
        });
    });
}

function initFormValidation() {
    const forms = document.querySelectorAll('form[novalidate]');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!this.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
            }
            this.classList.add('was-validated');
        });
    });
}

function initTooltip() {
    const tooltipTriggerList = [].slice.call(
        document.querySelectorAll('[data-bs-toggle="tooltip"]')
    );
    tooltipTriggerList.map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));
}

function initAutoRefresh() {
    const refreshInterval = document.querySelector('[data-auto-refresh]');
    if (refreshInterval) {
        const interval = parseInt(refreshInterval.dataset.autoRefresh) || 60000;
        setInterval(() => {
            location.reload();
        }, interval);
    }
}

function calculatePriorityPreview() {
    const safetyRisk = document.getElementById('safety_risk')?.value || 'no';
    const severity = document.getElementById('severity')?.value || 'minor';
    const urgency = document.getElementById('urgency')?.value || 'medium';

    const safetyScores = { no: 0, minor: 2, moderate: 5, severe: 8 };
    const severityScores = { minor: 1, moderate: 3, major: 5, critical: 8 };
    const urgencyScores = { low: 1, medium: 3, high: 5 };

    const totalScore = (safetyScores[safetyRisk] || 0) +
                       (severityScores[severity] || 0) +
                       (urgencyScores[urgency] || 0) + 5;

    let priorityLevel = 'low';
    if (totalScore >= 7.5) priorityLevel = 'high';
    else if (totalScore >= 4.0) priorityLevel = 'medium';

    const preview = document.getElementById('priorityPreview');
    if (preview) {
        preview.innerHTML = `
            <div class="text-center">
                <div class="priority-indicator mb-2">
                    <div class="priority-indicator-fill ${priorityLevel}" style="width: ${Math.min(100, (totalScore / 20) * 100)}%"></div>
                </div>
                <h3 class="priority-score priority-${priorityLevel}">${totalScore.toFixed(1)}</h3>
                <span class="priority-badge ${priorityLevel === 'high' ? 'priority-high' : priorityLevel === 'medium' ? 'priority-medium' : 'priority-low'}">
                    ${priorityLevel === 'high' ? 'High' : priorityLevel === 'medium' ? 'Medium' : 'Low'}
                </span>
            </div>
        `;
    }
}

document.addEventListener('change', function(e) {
    if (['safety_risk', 'severity', 'urgency'].includes(e.target.id)) {
        calculatePriorityPreview();
    }
});

function showAlert(message, type = 'info', duration = 5000) {
    const alertContainer = document.getElementById('alertContainer') || createAlertContainer();
    const alert = document.createElement('div');
    alert.className = `alert alert-${type} alert-bulsu alert-dismissible fade show`;
    alert.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'danger' ? 'exclamation-circle' : 'info-circle'} me-2"></i>
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    alertContainer.appendChild(alert);

    if (duration > 0) {
        setTimeout(() => {
            if (alert.parentNode) {
                alert.remove();
            }
        }, duration);
    }
}

function createAlertContainer() {
    const container = document.createElement('div');
    container.id = 'alertContainer';
    container.style.position = 'fixed';
    container.style.top = '20px';
    container.style.right = '20px';
    container.style.zIndex = '9999';
    container.style.maxWidth = '400px';
    document.body.appendChild(container);
    return container;
}

window.BulSU = {
    showAlert,
    calculatePriorityPreview,
    getStatusBadge: function(code) {
        const labels = {
            'submitted': 'Submitted', 'under_review': 'Under Review', 'validated': 'Validated',
            'assigned': 'Assigned', 'ongoing': 'Ongoing', 'resolved': 'Resolved',
            'closed': 'Closed', 'rejected': 'Rejected'
        };
        const classes = {
            'submitted': 'status-submitted', 'under_review': 'status-under_review',
            'validated': 'status-validated', 'assigned': 'status-assigned',
            'ongoing': 'status-ongoing', 'resolved': 'status-resolved',
            'closed': 'status-closed', 'rejected': 'status-rejected'
        };
        const label = labels[code] || code;
        const cls = classes[code] || 'secondary';
        return `<span class="status-badge ${cls}">${label}</span>`;
    },
    getPriorityBadge: function(level, score = null) {
        const labels = { 'high': 'High', 'medium': 'Medium', 'low': 'Low' };
        const classes = { 'high': 'priority-high', 'medium': 'priority-medium', 'low': 'priority-low' };
        const label = labels[level] || level;
        const cls = classes[level] || 'secondary';
        return `<span class="priority-badge ${cls}">${label}${score !== null ? ` <span class="priority-score">(${score})</span>` : ''}</span>`;
    },
};
