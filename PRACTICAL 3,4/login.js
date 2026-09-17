function validateform(event){
    //alert("JavaScript is working");
    let username = document.getElementById("Id").value;
    let password = document.getElementById("Password").value;
    if (username == ""){
        alert("Please enter username.");
        event.preventDefault();
    }
    if(password == ""){
        alert("Please enter Password.");
        event.preventDefault();
    }else if(password.length <= 8){
        alert("Password should be of at least 8 Characters.");
        event.preventDefault();
    }else if(!/[!@#$%^&*]/.test(password)){
        alert("Password does not contain any special character.");
        event.preventDefault();
    }
    if (username != "" && password.length >= 8 && /[!@#$%^&*]/.test(password)) 
        {
            alert("Login successful!");
        }
}