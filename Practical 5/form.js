function validateform(){
    let firstname = document.getElementById("firstname").value;
    let email = document.getElementById("email").value;
    let mobile = document.getElementById("mobile").value;
    let password = document.getElementById("password").value;
    let confirm = document.getElementById("confirm").value;

    let namePattern = /^[A-Za-z]+$/;
    let mobilePattern = /^[0-9]{10}$/;

    if(!namePattern.test(firstname)){
        alert("Please enter a valid name.");
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

    if (password != confirm) {
        alert("Passwords do not match!");
        return false;
    }

    alert("Registration Successful!");
    return true;
}