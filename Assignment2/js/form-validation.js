// Vue app for form validation
const { createApp, ref, onMounted, watch } = Vue;

createApp({
    setup() {
        // State definitions with ref
        const isRegisterActive = ref(false);
        const loginForm = ref({
            username: '',
            password: '',
            errorMessage: ''
        });
        const loginErrors = ref({
            username: false,
            password: false
        });
        const registerForm = ref({
            username: '',
            email: '',
            password: '',
            errorMessage: ''
        });
        const registerErrors = ref({
            username: false,
            email: false,
            password: false
        });
        
        // Methods
        const validateLoginForm = () => {
            let isValid = true;
            
            // Validate username
            if (!loginForm.value.username || loginForm.value.username.length < 3) {
                loginErrors.value.username = true;
                isValid = false;
            } else {
                loginErrors.value.username = false;
            }
            
            // Validate password
            if (!loginForm.value.password || loginForm.value.password.length < 6) {
                loginErrors.value.password = true;
                isValid = false;
            } else {
                loginErrors.value.password = false;
            }
            
            return isValid;
        };
        
        const validateRegisterForm = () => {
            let isValid = true;
            
            // Validate username
            if (!registerForm.value.username || registerForm.value.username.length < 3) {
                registerErrors.value.username = true;
                isValid = false;
            } else {
                registerErrors.value.username = false;
            }
            
            // Validate email
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!registerForm.value.email || !emailRegex.test(registerForm.value.email)) {
                registerErrors.value.email = true;
                isValid = false;
            } else {
                registerErrors.value.email = false;
            }
            
            // Validate password
            if (!registerForm.value.password || registerForm.value.password.length < 6) {
                registerErrors.value.password = true;
                isValid = false;
            } else {
                registerErrors.value.password = false;
            }
            
            return isValid;
        };
        
        const loginSubmit = () => {
            // Reset error message
            loginForm.value.errorMessage = '';
            
            if (validateLoginForm()) {
                // Prepare credentials
                const credentials = {
                    username: loginForm.value.username,
                    password: loginForm.value.password
                };
                
                console.log('Attempting login with:', loginForm.value.username);
                
                // Call login function
                fetch('php/login.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(credentials)
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! Status: ${response.status}`);
                    }
                    return response.text().then(text => {
                        try {
                            return JSON.parse(text);
                        } catch(e) {
                            console.error('Invalid JSON response:', text);
                            throw new Error('Server returned invalid JSON');
                        }
                    });
                })
                .then(data => {
                    console.log('Login response:', data);
                    if (data.success) {
                        // Store user data in localStorage
                        localStorage.setItem('currentUser', JSON.stringify(data.user));
                        
                        // Redirect to account page
                        window.location.href = 'account.html';
                    } else {
                        // Show error message
                        loginForm.value.errorMessage = data.message || 'Login failed. Please check your credentials.';
                    }
                })
                .catch(error => {
                    console.error('Login Error:', error);
                    loginForm.value.errorMessage = 'An error occurred. Please try again later.';
                });
            }
        };
        
        const registerSubmit = () => {
            // Reset error message
            registerForm.value.errorMessage = '';
            
            if (validateRegisterForm()) {
                // Create user object
                const newUser = {
                    username: registerForm.value.username,
                    email: registerForm.value.email,
                    password: registerForm.value.password
                };
                
                console.log('Attempting registration for:', registerForm.value.username);
                
                // Call register function
                fetch('php/register.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(newUser)
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! Status: ${response.status}`);
                    }
                    return response.text().then(text => {
                        try {
                            return JSON.parse(text);
                        } catch(e) {
                            console.error('Invalid JSON response:', text);
                            throw new Error('Server returned invalid JSON');
                        }
                    });
                })
                .then(data => {
                    console.log('Registration response:', data);
                    if (data.success) {
                        // Store user data in localStorage
                        localStorage.setItem('currentUser', JSON.stringify(data.user));
                        
                        // Redirect to account page
                        window.location.href = 'account.html';
                    } else {
                        // Show error message
                        registerForm.value.errorMessage = data.message || 'Registration failed. Please try again.';
                    }
                })
                .catch(error => {
                    console.error('Registration Error:', error);
                    registerForm.value.errorMessage = 'Registration failed: ' + error.message;
                });
            }
        };
        
        // Check database on page load
        const checkDatabase = () => {
            fetch('php/check_db.php')
                .then(response => response.json())
                .then(data => {
                    if (!data.success) {
                        console.error('Database check failed:', data.message);
                    } else {
                        console.log('Database check successful');
                    }
                })
                .catch(error => {
                    console.error('Database check error:', error);
                });
        };
        
        // Lifecycle hooks
        onMounted(() => {
            // Initialize database
            checkDatabase();
            
            // Check if user is already logged in
            const userData = localStorage.getItem('currentUser');
            if (userData) {
                try {
                    const user = JSON.parse(userData);
                    if (user && user.username) {
                        window.location.href = 'account.html';
                    }
                } catch (e) {
                    console.error('Error parsing user data:', e);
                    localStorage.removeItem('currentUser');
                }
            }
            
            // Add event listeners for toggle buttons
            document.querySelector('.register-btn').addEventListener('click', () => {
                isRegisterActive.value = true;
            });
            
            document.querySelector('.login-btn').addEventListener('click', () => {
                isRegisterActive.value = false;
            });
            
            // Add event listener for menu hover to adjust body padding
            const menu = document.querySelector('.menu');
            const body = document.body;
            
            if (menu) {
                menu.addEventListener('mouseenter', function() {
                    body.classList.add('menu-expanded');
                });
                
                menu.addEventListener('mouseleave', function() {
                    body.classList.remove('menu-expanded');
                });
            }
        });
        
        // Return the state and methods to be used in the template
        return {
            isRegisterActive,
            loginForm,
            loginErrors,
            registerForm,
            registerErrors,
            validateLoginForm,
            validateRegisterForm,
            loginSubmit,
            registerSubmit
        };
    }
}).mount('#app');
