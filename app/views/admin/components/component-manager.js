/**
 * Component Manager for Trang Sức Admin Dashboard
 * Handles loading and managing reusable components
 */

class ComponentManager {
    constructor() {
        this.components = {};
        this.basePath = 'app/views/admin/components/';
    }

    /**
     * Load a component from file
     */
    async loadComponent(componentName) {
        if (this.components[componentName]) {
            return this.components[componentName];
        }

        try {
            // Try PHP component first, fallback to HTML
            let response = await fetch(`${this.basePath}${componentName}.php`);
            if (!response.ok) {
                response = await fetch(`${this.basePath}${componentName}.html`);
            }
            
            if (!response.ok) {
                throw new Error(`Failed to load component: ${componentName}`);
            }
            
            const html = await response.text();
            this.components[componentName] = html;
            return html;
        } catch (error) {
            console.error(`Error loading component ${componentName}:`, error);
            return '';
        }
    }

    /**
     * Render sidebar component
     */
    async renderSidebar(config = {}) {
        const html = await this.loadComponent('sidebar');
        const container = document.getElementById('sidebar-container');
        if (container) {
            container.innerHTML = html;
            this.configureSidebar(config);
        }
    }

    /**
     * Configure sidebar with specific settings
     */
    configureSidebar(config) {
        const sidebar = document.querySelector('.sidebar');
        if (!sidebar) return;

        // Set brand name
        if (config.brandName) {
            const brandElement = sidebar.querySelector('[data-brand]');
            if (brandElement) {
                brandElement.textContent = config.brandName;
            }
        }

        // Set active page
        if (config.activePage) {
            sidebar.querySelectorAll('.sidebar-nav-link').forEach(link => {
                link.classList.remove('active');
                const linkPage = link.getAttribute('data-page');
                if (linkPage === config.activePage) {
                    link.classList.add('active');
                }
            });
        }

        // Configure categories
        if (config.categories) {
            const categoriesList = sidebar.querySelector('#categories-list');
            const categoriesTitle = sidebar.querySelector('[data-categories-title]');
            
            if (categoriesTitle && config.categoriesTitle) {
                categoriesTitle.textContent = config.categoriesTitle;
            }
            
            if (categoriesList && config.categories.length > 0) {
                categoriesList.innerHTML = config.categories.map(cat => `
                    <div class="d-flex justify-content-between text-light">
                        <span>${cat.name}</span>
                        <span class="badge bg-secondary">${cat.count}</span>
                    </div>
                `).join('');
            }
        }

        // Set navigation links
        if (config.links) {
            this.updateSidebarLinks(config.links);
        }
    }

    /**
     * Update sidebar navigation links
     */
    updateSidebarLinks(links) {
        const navLinks = document.querySelectorAll('.sidebar-nav-link');
        navLinks.forEach(link => {
            const page = link.getAttribute('data-page');
            if (links[page]) {
                link.href = links[page];
            }
        });
    }

    /**
     * Render header component
     */
    async renderHeader(config = {}) {
        const html = await this.loadComponent('header');
        const container = document.getElementById('header-container');
        if (container) {
            container.innerHTML = html;
            this.configureHeader(config);
        }
    }

    /**
     * Configure header with specific settings
     */
    configureHeader(config) {
        const header = document.querySelector('.header');
        if (!header) return;

        // Set page title
        if (config.title) {
            const titleElement = header.querySelector('[data-page-title]');
            if (titleElement) {
                titleElement.textContent = config.title;
            }
        }

        // Set breadcrumb
        if (config.breadcrumb) {
            const breadcrumbElement = header.querySelector('[data-breadcrumb]');
            if (breadcrumbElement) {
                breadcrumbElement.textContent = config.breadcrumb;
            }
        }

        // Show/hide date range
        if (config.showDateRange) {
            const dateRangeSection = header.querySelector('#date-range-section');
            if (dateRangeSection) {
                dateRangeSection.style.display = 'flex';
                if (config.dateRange) {
                    const dateRangeElement = header.querySelector('[data-date-range]');
                    if (dateRangeElement) {
                        dateRangeElement.textContent = config.dateRange;
                    }
                }
            }
        }
    }

    /**
     * Initialize components for a page
     */
    async initializePage(pageConfig) {
        // Render sidebar
        if (pageConfig.sidebar !== false) {
            await this.renderSidebar(pageConfig.sidebar || {});
        }

        // Render header
        if (pageConfig.header !== false) {
            await this.renderHeader(pageConfig.header || {});
        }
    }
}

// Create global instance
window.ComponentManager = new ComponentManager();

// Auto-initialize if page config exists
document.addEventListener('DOMContentLoaded', () => {
    if (window.pageConfig) {
        window.ComponentManager.initializePage(window.pageConfig);
    }
});