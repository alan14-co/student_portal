document.addEventListener('DOMContentLoaded', function () {

    // ===== AJAX Search & Pagination for Admin Students List =====
    const searchInput = document.getElementById('searchInput');
    const studentsBody = document.getElementById('studentsBody');
    const paginationList = document.getElementById('paginationList');

    if (searchInput && studentsBody) {
        let currentPage = 1;
        let searchTimeout = null;

        function loadStudents(page = 1, search = '') {
            currentPage = page;
            fetch(`../ajax_search.php?search=${encodeURIComponent(search)}&page=${page}`)
                .then(response => response.json())
                .then(data => {
                    renderStudents(data.students, page);
                    renderPagination(data.totalPages, page, search);
                })
                .catch(err => console.error('Search error:', err));
        }

        function renderStudents(students, page) {
            studentsBody.innerHTML = '';
            if (students.length === 0) {
                studentsBody.innerHTML = '<tr><td colspan="8" class="text-center text-muted">No students found.</td></tr>';
                return;
            }

            let index = (page - 1) * 5 + 1;
            students.forEach(student => {
                const statusBadge = student.status === 'Active'
                    ? '<span class="badge bg-success">Active</span>'
                    : '<span class="badge bg-secondary">Inactive</span>';

                const img = student.profile_image
                    ? `../assets/uploads/${student.profile_image}`
                    : '../assets/uploads/default.png';

                const row = `
                    <tr>
                        <td>${index++}</td>
                        <td><img src="${img}" onerror="this.src='../assets/uploads/default.png'" class="rounded-circle" width="40" height="40" alt="profile"></td>
                        <td>${escapeHtml(student.full_name)}</td>
                        <td>${escapeHtml(student.email)}</td>
                        <td>${escapeHtml(student.phone || '')}</td>
                        <td>${escapeHtml(student.course)}</td>
                        <td>${statusBadge}</td>
                        <td>
                            <a href="view_student.php?id=${student.id}" class="btn btn-sm btn-info" title="View"><i class="bi bi-eye"></i></a>
                            <a href="edit_student.php?id=${student.id}" class="btn btn-sm btn-warning" title="Edit"><i class="bi bi-pencil"></i></a>
                            <a href="delete_student.php?id=${student.id}" class="btn btn-sm btn-danger" title="Delete" onclick="return confirm('Are you sure you want to delete this student?');"><i class="bi bi-trash"></i></a>
                        </td>
                    </tr>
                `;
                studentsBody.insertAdjacentHTML('beforeend', row);
            });
        }

        function renderPagination(totalPages, currentPage, search) {
            if (!paginationList) return;
            paginationList.innerHTML = '';

            for (let p = 1; p <= totalPages; p++) {
                const activeClass = p === currentPage ? 'active' : '';
                const li = document.createElement('li');
                li.className = `page-item ${activeClass}`;
                li.innerHTML = `<a class="page-link page-link-ajax" href="?page=${p}&search=${encodeURIComponent(search)}" data-page="${p}">${p}</a>`;
                paginationList.appendChild(li);
            }

            attachPaginationEvents(search);
        }

        function attachPaginationEvents(search) {
            document.querySelectorAll('.page-link-ajax').forEach(link => {
                link.addEventListener('click', function (e) {
                    e.preventDefault();
                    const page = parseInt(this.dataset.page);
                    loadStudents(page, search);
                });
            });
        }

        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Attach pagination events on initial page load
        attachPaginationEvents(searchInput.value);

        // Search input event with debounce
        searchInput.addEventListener('input', function () {
            clearTimeout(searchTimeout);
            const value = this.value;
            searchTimeout = setTimeout(() => {
                loadStudents(1, value);
            }, 400);
        });
    }

    // ===== Profile Image Preview =====
    const profileImageInput = document.getElementById('profileImageInput');
    const profilePreview = document.getElementById('profilePreview');

    if (profileImageInput && profilePreview) {
        profileImageInput.addEventListener('change', function () {
            const file = this.files[0];
            if (file) {
                const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png'];
                if (!allowedTypes.includes(file.type)) {
                    alert('Only JPG, JPEG, PNG files are allowed.');
                    this.value = '';
                    return;
                }
                const reader = new FileReader();
                reader.onload = function (e) {
                    profilePreview.src = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        });
    }

    // ===== Simple Image Preview on Select =====
    // For any file input that has a corresponding preview image element
    document.querySelectorAll('input[type="file"]').forEach(function (input) {
        const previewTarget = document.getElementById(input.id + '-preview');
        const hiddenField = document.getElementById(input.id + '-edited');
        if (!previewTarget) return;
        input.addEventListener('change', function () {
            const file = this.files[0];
            if (!file) return;
            const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png'];
            if (!allowedTypes.includes(file.type)) {
                alert('Only JPG, JPEG, PNG files are allowed.');
                this.value = '';
                return;
            }
            const reader = new FileReader();
            reader.onload = function (e) {
                previewTarget.src = e.target.result;
                if (hiddenField) hiddenField.value = '';
            };
            reader.readAsDataURL(file);
        });
    });

    // ===== Password Match Validation on Registration =====
    const registerForm = document.querySelector('form[action="register.php"]');
    if (registerForm) {
        registerForm.addEventListener('submit', function (e) {
            const password = this.querySelector('input[name="password"]');
            const confirmPassword = this.querySelector('input[name="confirm_password"]');
            if (password.value !== confirmPassword.value) {
                e.preventDefault();
                alert('Passwords do not match!');
            }
        });
    }

});
