function validateform() {
    let firstname = document.getElementById("firstname").value;
    let middlename = document.getElementById("middlename").value;
    let lastname = document.getElementById("lastname").value;
    let email = document.getElementById("email").value;
    let mobile = document.getElementById("mobile").value;
    let password = document.getElementById("password").value;
    let confirm = document.getElementById("confirm").value;
    let namePattern = /^[A-Za-z]+$/;
    let mobilePattern = /^[0-9]{10}$/;
    let emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!namePattern.test(firstname)) {
        alert("Please enter a valid name.");
        return false;
    }
    if (!namePattern.test(middlename)) {
        alert("Please enter a valid name.");
        return false;
    }
    if (!namePattern.test(lastname)) {
        alert("Please enter a valid name.");
        return false;
    }
    if (!emailPattern.test(email)) {
        alert("Please enter a valid email.");
        return false;
    }
    if (!mobilePattern.test(mobile)) {
        alert("Mobile number must have 10 digits!");
        return false;
    }
    if (password.length < 8) {
        alert("Password must have at least 8 characters!");
        return false;
    }
    if (!/[!@#$%^&*]/.test(password)) {
        alert("Password must contain a special character!");
        return false;
    }
    if (password != confirm) {
        alert("Passwords do not match!");
        return false;
    }
    localStorage.setItem("username", email);
    localStorage.setItem("password", password);
    alert("Registration Successful!");
    window.location.href = "login.html";
    return false;
}