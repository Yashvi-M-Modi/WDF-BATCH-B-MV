function validateform(event) {
    let username = document.getElementById("Id").value;
    let password = document.getElementById("Password").value;
    let savedusername = localStorage.getItem("username");
    let savedpassword = localStorage.getItem("password");
    if (username == "") {
        alert("Please enter username.");
        event.preventDefault();
        return;
    }
    if (password == "") {
        alert("Please enter Password.");
        event.preventDefault();
        return;
    }
    if (password.length < 8) {
        alert("Password should be of at least 8 Characters.");
        event.preventDefault();
        return;
    }
    if (!/[!@#$%^&*]/.test(password)) {
        alert("Password does not contain any special character.");
        event.preventDefault();
        return;
    }
    if (username != savedusername || password != savedpassword) {
        alert("Invalid username or password.");
        event.preventDefault();
        return;
    }
    alert("Login successful!");
}