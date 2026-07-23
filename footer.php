            <!-- Footer -->
            <footer class="footer mt-auto py-3 bg-light border-top text-center" style="font-size: 13px; color: var(--text-muted);">
                <div class="container d-flex flex-column flex-md-row justify-content-between align-items-center">
                    <span>&copy; 2026 Sandip University. All Rights Reserved.</span>
                    <div class="d-flex gap-3 mt-2 mt-md-0">
                        <a href="#" class="text-decoration-none text-muted" id="help-desk-link">Help Desk</a>
                        <span class="text-muted">|</span>
                        <a href="#" class="text-decoration-none text-muted" id="privacy-link">Privacy Policy</a>
                        <span class="text-muted">|</span>
                        <span>Contact: support@sandip.edu.in</span>
                        <span class="text-muted">|</span>
                        <span>v2.0</span>
                    </div>
                </div>
            </footer>

        </div> <!-- Close .main-content -->
    </div> <!-- Close #app-container -->

    <!-- ==========================================
         MODALS (Help Desk and Privacy Policy)
         ========================================== -->

    <!-- Help Desk Modal -->
    <div class="modal-backdrop-custom" id="help-modal">
        <div class="modal-content-custom">
            <form action="help_ticket.php" method="POST">
                <div class="modal-header-custom">
                    <h3>Help Desk Support</h3>
                    <button type="button" class="modal-close-btn" id="help-close-btn">&times;</button>
                </div>
                <div class="modal-body-custom">
                    <p class="text-muted font-size-13">Submit a ticket to the university IT desk and we will get back to you shortly.</p>
                    <div class="mb-3">
                        <label class="form-label font-size-13 font-weight-600">Subject</label>
                        <input type="text" name="subject" class="form-control" placeholder="e.g. Can't schedule meeting, Calendar issue" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label font-size-13 font-weight-600">Details</label>
                        <textarea name="message" class="form-control" rows="4" placeholder="Describe the issue you are facing..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer-custom">
                    <button type="button" class="btn btn-secondary py-2 px-3" id="help-cancel-btn">Cancel</button>
                    <button type="submit" class="btn btn-primary py-2 px-4" style="background-color: var(--primary-color); border-color: var(--primary-color);">Submit Ticket</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Privacy Policy Modal -->
    <div class="modal-backdrop-custom" id="privacy-modal">
        <div class="modal-content-custom" style="max-width: 600px;">
            <div class="modal-header-custom">
                <h3>Privacy Policy</h3>
                <button type="button" class="modal-close-btn" id="privacy-close-btn">&times;</button>
            </div>
            <div class="modal-body-custom" style="max-height: 400px; overflow-y: auto; font-size: 14px;">
                <h5 class="font-weight-600">1. Data Storage & Usage</h5>
                <p>Sandip University is committed to protecting all internal communication, schedules, documents, and logs. Data entered here is strictly for academic and administrative planning purposes.</p>
                <h5 class="font-weight-600 mt-3">2. Document Storage</h5>
                <p>Documents uploaded to this system are stored securely. Access permissions are limited strictly based on roles (Registrar, Dean, Faculty, Coordinator, Admin) as outlined in the university governance charter.</p>
                <h5 class="font-weight-600 mt-3">3. Cookies & Session Management</h5>
                <p>We use standard PHP session variables and cookie tokens to authenticate users. No tracking cookies are deployed.</p>
            </div>
            <div class="modal-footer-custom">
                <button type="button" class="btn btn-primary py-2 px-4" id="privacy-ok-btn" style="background-color: var(--primary-color); border-color: var(--primary-color);">Understood</button>
            </div>
        </div>
    </div>

    <!-- ==========================================
         DEMO ROLE SWITCHER (PERSISTENT DEV BAR)
         ========================================== -->
    <div class="dev-panel d-none d-md-flex">
        <div class="dev-left">
            <span class="dev-badge">DEMO PANEL</span>
            <span>Switch roles instantly for review/grading:</span>
        </div>
        <div class="dev-right">
            <form action="login.php?action=switch" method="POST" class="d-inline">
                <select name="user_id" onchange="this.form.submit()" class="dev-select">
                    <option value="">-- Choose Role --</option>
                    <option value="1" <?php echo ($_SESSION['user_id'] ?? '') == 1 ? 'selected' : ''; ?>>Admin (Dr. Amit Patel)</option>
                    <option value="2" <?php echo ($_SESSION['user_id'] ?? '') == 2 ? 'selected' : ''; ?>>Registrar/VC (Prof. Rajendra Prasad)</option>
                    <option value="3" <?php echo ($_SESSION['user_id'] ?? '') == 3 ? 'selected' : ''; ?>>Dean/HOD (Dr. Sandeep Sharma)</option>
                    <option value="4" <?php echo ($_SESSION['user_id'] ?? '') == 4 ? 'selected' : ''; ?>>Faculty/Staff (Prof. Neha Gupta)</option>
                    <option value="5" <?php echo ($_SESSION['user_id'] ?? '') == 5 ? 'selected' : ''; ?>>Coordinator (Mr. Vicky Verma)</option>
                </select>
            </form>
        </div>
    </div>

    <!-- UI Interaction Scripts -->
    <script>
        // Initialize Lucide Icons
        lucide.createIcons();

        // Sidebar Toggle
        const toggleSidebar = document.getElementById('toggle-sidebar');
        const sidebar = document.getElementById('sidebar');
        const mainContent = document.querySelector('.main-content');
        const topHeader = document.querySelector('.top-header');

        if (toggleSidebar && sidebar) {
            toggleSidebar.addEventListener('click', function() {
                sidebar.classList.toggle('collapsed');
                sidebar.classList.toggle('active');
                if (mainContent) mainContent.classList.toggle('expanded');
                if (topHeader) topHeader.classList.toggle('expanded');
            });
        }

        // Language Switch Toggle
        const langToggle = document.getElementById('lang-toggle');
        const langLabel = document.getElementById('lang-label');
        const translations = {
            en: {
                searchPlaceholder: 'Search tasks, meetings...',
                notifications: 'Notifications',
                markAllRead: 'Mark all read',
                viewAllNotifications: 'View all notifications',
                noNotifications: 'No notifications'
            },
            hi: {
                searchPlaceholder: 'कार्य, मीटिंग खोजें...',
                notifications: 'अधिसूचनाएँ',
                markAllRead: 'सभी पढ़ें',
                viewAllNotifications: 'सभी अधिसूचनाएँ देखें',
                noNotifications: 'कोई अधिसूचना नहीं'
            }
        };

        let currentLang = '<?php echo $lang; ?>';

        const applyLanguage = (lang) => {
            currentLang = lang;
            document.body.dataset.lang = lang;
            if (langLabel) {
                langLabel.textContent = lang === 'hi' ? 'HI' : 'EN';
            }

            document.querySelectorAll('[data-i18n]').forEach(el => {
                const key = el.getAttribute('data-i18n');
                if (translations[lang] && translations[lang][key]) {
                    el.textContent = translations[lang][key];
                }
            });

            const searchInput = document.querySelector('input[name="search"]');
            if (searchInput && translations[lang]) {
                searchInput.placeholder = translations[lang].searchPlaceholder;
            }

            document.cookie = 'site_lang=' + lang + '; path=/; max-age=31536000';
        };

        applyLanguage(currentLang);

        if (langToggle) {
            langToggle.addEventListener('click', function() {
                const nextLang = currentLang === 'hi' ? 'en' : 'hi';
                applyLanguage(nextLang);
                langToggle.setAttribute('aria-label', nextLang === 'hi' ? 'Switch to English' : 'Switch to Hindi');
            });
        }

        // Notification Bell Toggle
        const notiToggle = document.getElementById('noti-toggle');
        const notiDropdown = document.getElementById('noti-dropdown');

        if (notiToggle && notiDropdown) {
            notiToggle.addEventListener('click', function(e) {
                e.stopPropagation();
                notiDropdown.classList.toggle('active');
                const profileDropdown = document.getElementById('profile-dropdown');
                if (profileDropdown) profileDropdown.classList.remove('active');
            });
        }

        // Profile Menu Toggle
        const profileToggle = document.getElementById('profile-toggle');
        const profileDropdown = document.getElementById('profile-dropdown');

        if (profileToggle && profileDropdown) {
            profileToggle.addEventListener('click', function(e) {
                e.stopPropagation();
                profileDropdown.classList.toggle('active');
                if (notiDropdown) notiDropdown.classList.remove('active');
            });
        }

        // Close dropdowns when clicking elsewhere
        document.addEventListener('click', function() {
            if (notiDropdown) notiDropdown.classList.remove('active');
            if (profileDropdown) profileDropdown.classList.remove('active');
        });

        // Modals Toggle
        const helpModal = document.getElementById('help-modal');
        const helpLink = document.getElementById('help-desk-link');
        const helpClose = document.getElementById('help-close-btn');
        const helpCancel = document.getElementById('help-cancel-btn');

        if (helpLink && helpModal) {
            helpLink.addEventListener('click', function(e) {
                e.preventDefault();
                helpModal.classList.add('show');
            });
        }
        [helpClose, helpCancel].forEach(btn => {
            if (btn && helpModal) {
                btn.addEventListener('click', () => helpModal.classList.remove('show'));
            }
        });

        const privacyModal = document.getElementById('privacy-modal');
        const privacyLink = document.getElementById('privacy-link');
        const privacyClose = document.getElementById('privacy-close-btn');
        const privacyOk = document.getElementById('privacy-ok-btn');

        if (privacyLink && privacyModal) {
            privacyLink.addEventListener('click', function(e) {
                e.preventDefault();
                privacyModal.classList.add('show');
            });
        }
        [privacyClose, privacyOk].forEach(btn => {
            if (btn && privacyModal) {
                btn.addEventListener('click', () => privacyModal.classList.remove('show'));
            }
        });
    </script>
</body>
</html>
