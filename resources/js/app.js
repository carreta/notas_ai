// Character counter for home page
document.addEventListener('DOMContentLoaded', function() {
    const textarea = document.getElementById('transcript');
    const countEl = document.getElementById('char-count-value');
    const maxEl = document.getElementById('char-count-max');
    const select = document.getElementById('model-selection');
    const counter = document.getElementById('char-count');

    if (textarea && countEl && maxEl && select && counter) {
        function updateCounter() {
            const count = textarea.value.length;
            countEl.textContent = count.toLocaleString();

            const selectedOption = select.selectedOptions[0];
            const max = selectedOption ? parseInt(selectedOption.dataset.maxChars, 10) : parseInt(maxEl.textContent.replace(/,/g, ''), 10);
            maxEl.textContent = max.toLocaleString();

            if (count > max) {
                counter.classList.add('text-error');
                if (!counter.querySelector('.warning-icon')) {
                    const icon = document.createElement('span');
                    icon.className = 'warning-icon material-symbols-outlined';
                    icon.textContent = 'warning ';
                    icon.style.fontVariationSettings = "'FILL' 1";
                    counter.prepend(icon);
                    counter.style.color = 'var(--color-error)';
                }
            } else {
                counter.classList.remove('text-error');
                const icon = counter.querySelector('.warning-icon');
                if (icon) icon.remove();
                counter.style.color = 'var(--color-outline)';
            }
        }

        textarea.addEventListener('input', updateCounter);
        select.addEventListener('change', updateCounter);
        updateCounter(); // initial
    }

    // History page modal logic
    window.openHistoryModal = function(meetingId) {
        const modal = document.getElementById('historyModal');
        const modalContent = document.getElementById('historyModalContent');
        if (modal && modalContent) {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            setTimeout(() => {
                modalContent.classList.remove('scale-95', 'opacity-0');
                modalContent.classList.add('scale-100', 'opacity-100');
            }, 10);
            // TODO: Load meeting data by ID
        }
    };

    window.closeHistoryModal = function() {
        const modal = document.getElementById('historyModal');
        const modalContent = document.getElementById('historyModalContent');
        if (modal && modalContent) {
            modalContent.classList.remove('scale-100', 'opacity-100');
            modalContent.classList.add('scale-95', 'opacity-0');
            setTimeout(() => {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }, 200);
        }
    };

    window.switchHistoryTab = function(tabId) {
        const tabAnalysis = document.getElementById('tab-analysis');
        const tabTranscript = document.getElementById('tab-transcript');
        const contentAnalysis = document.getElementById('content-analysis');
        const contentTranscript = document.getElementById('content-transcript');

        if (tabId === 'analysis') {
            tabAnalysis.classList.replace('text-on-surface-variant', 'text-primary');
            tabAnalysis.classList.replace('border-transparent', 'border-primary');
            tabAnalysis.classList.remove('hover:text-on-surface');
            tabTranscript.classList.replace('text-primary', 'text-on-surface-variant');
            tabTranscript.classList.replace('border-primary', 'border-transparent');
            tabTranscript.classList.add('hover:text-on-surface');
            contentAnalysis.classList.remove('hidden');
            contentTranscript.classList.add('hidden');
        } else {
            tabTranscript.classList.replace('text-on-surface-variant', 'text-primary');
            tabTranscript.classList.replace('border-transparent', 'border-primary');
            tabTranscript.classList.remove('hover:text-on-surface');
            tabAnalysis.classList.replace('text-primary', 'text-on-surface-variant');
            tabAnalysis.classList.replace('border-primary', 'border-transparent');
            tabAnalysis.classList.add('hover:text-on-surface');
            contentTranscript.classList.remove('hidden');
            contentAnalysis.classList.add('hidden');
        }
    };

    // Debug page view switching
    window.showView = function(viewId) {
        const viewMain = document.getElementById('view-main');
        const viewLogs = document.getElementById('view-logs');
        const pageHeader = document.getElementById('page-header');

        if (viewMain && viewLogs) {
            viewMain.classList.remove('view-active');
            viewMain.classList.add('view-hidden');
            viewLogs.classList.remove('view-active');
            viewLogs.classList.add('view-hidden');

            document.getElementById(viewId).classList.remove('view-hidden');
            document.getElementById(viewId).classList.add('view-active');

            if (viewId === 'view-logs' && pageHeader) {
                pageHeader.classList.add('hidden');
            } else if (pageHeader) {
                pageHeader.classList.remove('hidden');
            }
        }
    };

    // Debug page log modal
    window.openLogModal = function() {
        const modal = document.getElementById('logModal');
        if (modal) {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }
    };

    window.closeLogModal = function() {
        const modal = document.getElementById('logModal');
        if (modal) {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
    };

    window.switchLogTab = function(tabId) {
        const metadataTab = document.getElementById('metadataTabContent');
        const promptTab = document.getElementById('promptTabContent');
        const metadataBtn = document.querySelector('[onclick="switchLogTab(\'metadata\')"]');
        const promptBtn = document.querySelector('[onclick="switchLogTab(\'prompt\')"]');

        if (tabId === 'metadata') {
            metadataTab.classList.remove('hidden');
            promptTab.classList.add('hidden');
            if (metadataBtn) { metadataBtn.classList.replace('text-on-surface-variant', 'text-primary'); metadataBtn.classList.replace('border-transparent', 'border-primary'); metadataBtn.classList.remove('hover:text-on-surface'); }
            if (promptBtn) { promptBtn.classList.replace('text-primary', 'text-on-surface-variant'); promptBtn.classList.replace('border-primary', 'border-transparent'); promptBtn.classList.add('hover:text-on-surface'); }
        } else {
            promptTab.classList.remove('hidden');
            metadataTab.classList.add('hidden');
            if (promptBtn) { promptBtn.classList.replace('text-on-surface-variant', 'text-primary'); promptBtn.classList.replace('border-transparent', 'border-primary'); promptBtn.classList.remove('hover:text-on-surface'); }
            if (metadataBtn) { metadataBtn.classList.replace('text-primary', 'text-on-surface-variant'); metadataBtn.classList.replace('border-primary', 'border-transparent'); metadataBtn.classList.add('hover:text-on-surface'); }
        }
    };

    // Close modals on Escape key
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            const historyModal = document.getElementById('historyModal');
            const logModal = document.getElementById('logModal');
            if (historyModal && !historyModal.classList.contains('hidden')) {
                closeHistoryModal();
            }
            if (logModal && !logModal.classList.contains('hidden')) {
                closeLogModal();
            }
        }
    });

    // Close modal on backdrop click
    document.addEventListener('click', function(event) {
        const historyModal = document.getElementById('historyModal');
        const historyModalContent = document.getElementById('historyModalContent');
        if (historyModal && !historyModal.classList.contains('hidden') && event.target === historyModal) {
            closeHistoryModal();
        }
    });
});