function logout(event){
    let answer = confirm("Are you sure you want to logout?");
    if(answer == true){
        alert("You have successfully logged out from account.");
    }
    if(answer == false){
        event.preventDefault();
    }
}