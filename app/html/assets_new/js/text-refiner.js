// text-refiner.js - Reusable text refinement component
class TextRefiner {
    constructor() {
        this.init();
    }

    init() {
        // Add CSS styles
        this.addStyles();

        // Watch for textareas dynamically added to the page
        this.observeTextareas();

        // Process existing textareas
        document.querySelectorAll('textarea').forEach(textarea => {
            if (!textarea.classList.contains('no-refiner')) {
                this.addRefinerButton(textarea);
            }
        });
    }

    addStyles() {
        const style = document.createElement('style');
        style.textContent = `
            .refiner-container {
                position: relative;
                display: inline-block;
                width: 100%;
            }
            
            .refiner-icon {
                position: absolute;
                bottom: 8px;
                right: 8px;
                background: #f0f0f0;
                border: 1px solid #ccc;
                border-radius: 4px;
                width: 30px;
                height: 30px;
                display: flex;
                align-items: center;
                justify-content: center;
                cursor: pointer;
                font-size: 16px;
                z-index: 10;
                transition: all 0.2s;
            }
            
            .refiner-icon:hover {
                background: #e0e0e0;
            }
            
            .refiner-options {
                position: absolute;
                bottom: 40px;
                right: 0;
                background: white;
                border: 1px solid #ddd;
                border-radius: 6px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                padding: 8px;
                z-index: 100;
                display: none;
                min-width: 150px;
            }
            
            .refiner-options.show {
                display: block;
            }
            
            .refiner-option {
                display: block;
                width: 100%;
                padding: 8px 12px;
                border: none;
                background: none;
                text-align: left;
                cursor: pointer;
                border-radius: 4px;
                margin-bottom: 4px;
            }
            
            .refiner-option:hover {
                background: #f5f5f5;
            }
            
            .refiner-option.professional::before {
                content: "👔 ";
            }
            
            .refiner-option.friendly::before {
                content: "😊 ";
            }
            
            .refining {
                opacity: 0.7;
                pointer-events: none;
            }
            
            .refining::placeholder {
                color: #999;
            }
        `;
        document.head.appendChild(style);
    }

    addRefinerButton(textarea) {
        // Check if already has refiner
        if (textarea.parentNode.classList.contains('refiner-container')) {
            return;
        }

        // Wrap textarea in container
        const container = document.createElement('div');
        container.className = 'refiner-container';

        // Insert container before textarea
        textarea.parentNode.insertBefore(container, textarea);
        container.appendChild(textarea);

        // Create refiner icon
        const icon = document.createElement('div');
        icon.className = 'refiner-icon';
        icon.innerHTML = '✨';
        icon.title = 'Rephrase text';
        container.appendChild(icon);

        // Create options dropdown
        const options = document.createElement('div');
        options.className = 'refiner-options';

        const professionalBtn = document.createElement('button');
        professionalBtn.className = 'refiner-option professional';
        professionalBtn.textContent = 'Professional';
        professionalBtn.onclick = () => this.refineText(textarea, 'professional');

        const friendlyBtn = document.createElement('button');
        friendlyBtn.className = 'refiner-option friendly';
        friendlyBtn.textContent = 'Friendly';
        friendlyBtn.onclick = () => this.refineText(textarea, 'friendly');

        options.appendChild(professionalBtn);
        options.appendChild(friendlyBtn);
        container.appendChild(options);

        // Toggle options on icon click
        icon.onclick = (e) => {
            e.stopPropagation();
            options.classList.toggle('show');
        };

        // Close options when clicking elsewhere
        document.addEventListener('click', () => {
            options.classList.remove('show');
        });
    }

    async refineText(textarea, tone) {
        const originalText = textarea.value.trim();

        if (originalText.length < 5) {
            alert("Please write a bit more to rephrase (minimum 5 characters).");
            return;
        }

        // Close options
        textarea.parentNode.querySelector('.refiner-options').classList.remove('show');

        // Visual feedback
        const originalPlaceholder = textarea.placeholder;
        const originalValue = textarea.value;

        textarea.classList.add('refining');
        textarea.placeholder = "Rephrasing...";
        textarea.value = "";

        try {
            const response = await fetch('rephrase.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ text: originalText, tone: tone })
            });

            const data = await response.json();

            if (data.newText && data.newText !== "Error") {
                textarea.value = data.newText;
            } else {
                textarea.value = originalValue;
                alert("Sorry, couldn't rephrase at the moment. Please try again.");
            }
        } catch (error) {
            console.error("Error:", error);
            textarea.value = originalValue;
            alert("Network error. Please check your connection.");
        } finally {
            textarea.classList.remove('refining');
            textarea.placeholder = originalPlaceholder;
        }
    }

    observeTextareas() {
        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                mutation.addedNodes.forEach((node) => {
                    if (node.nodeName === 'TEXTAREA') {
                        this.addRefinerButton(node);
                    }
                    // Also check for textareas inside added nodes
                    if (node.querySelectorAll) {
                        node.querySelectorAll('textarea').forEach(textarea => {
                            this.addRefinerButton(textarea);
                        });
                    }
                });
            });
        });

        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    window.textRefiner = new TextRefiner();
});