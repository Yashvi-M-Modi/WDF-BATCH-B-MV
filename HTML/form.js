function validateform() {
    let firstname = document.getElementById("firstname").value.trim();
    let middlename = document.getElementById("middlename").value.trim();
    let lastname = document.getElementById("lastname").value.trim();
    let username = document.getElementById("username").value.trim();
    let email = document.getElementById("email").value.trim();
    let mobile = document.getElementById("mobile").value.trim();
    let password = document.getElementById("password").value;
    let confirm = document.getElementById("confirm").value;
    let course = document.getElementById("course").value;
    let gender = document.querySelector('input[name="gender"]:checked');
    let terms = document.getElementById("terms").checked;

    let namePattern = /^[A-Za-z]+$/;
    let usernamePattern = /^[A-Za-z0-9_]{3,20}$/;
    let mobilePattern = /^[0-9]{10}$/;
    let emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

    if (!namePattern.test(firstname) || !namePattern.test(middlename) || !namePattern.test(lastname)) {
        alert("Names must contain only letters.");
        return false;
    }

    if (!usernamePattern.test(username)) {
        alert("Username must be 3-20 characters using letters, numbers, or underscores.");
        return false;
    }

    if (!emailPattern.test(email)) {
        alert("Please enter a valid email.");
        return false;
    }

    if (!mobilePattern.test(mobile)) {
        alert("Mobile number must have 10 digits.");
        return false;
    }

    if (password.length < 8 || !/[!@#$%^&*]/.test(password)) {
        alert("Password must have at least 8 characters and one special character.");
        return false;
    }

    if (password !== confirm) {
        alert("Passwords do not match.");
        return false;
    }

    if (course === "") {
        alert("Please select a course.");
        return false;
    }

    if (!gender) {
        alert("Please select a gender.");
        return false;
    }

    if (!terms) {
        alert("Please accept the terms and conditions.");
        return false;
    }

    return true;
}