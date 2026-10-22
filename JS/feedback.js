fetch("../JSON/teacher.json")
    .then(response => response.json())
    .then(data => {
        let select = document.getElementById("teacher")
        data.forEach(teacher => {
            let option = document.createElement("option");
            option.value = teacher.teacher;
            option.textContent = teacher.teacher + " - " + teacher.code + " - " + teacher.type;
            select.appendChild(option);
        });
    });
let form = document.querySelector("form");
form.addEventListener("submit", function(event) {
    event.preventDefault();
    let name = document.getElementById("name").value;
    let enrollment = document.getElementById("enrollment").value;
    let teacher = document.getElementById("teacher").value;
    let feedback = document.querySelector('input[name="Feedback"]:checked').value;
    alert("Name: " + name +
        "\nEnrollment No: " + enrollment +
        "\nTeacher: " + teacher +
        "\nFeedback: " + feedback);

});