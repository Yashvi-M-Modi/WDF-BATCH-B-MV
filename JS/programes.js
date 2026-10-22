function registerprogrames(event){
    event.preventDefault();
    alert("You Have Successfully registered for our Courses.");
}
fetch("../Practical 6/programmes.json")
    .then(response => response.json())
    .then(data => {
        let table = getElementById("table");
        data.forEach(element => {
            let row = `
            <tr>
                <td><input type = "checkbox"></td>
                <td>${programmes.code}</td>
                <td>${programmes.name}</td>
                <td>${programmes.credits}</td>
            </tr>`;
            table = innerHTML += row;
        });
    });
let table = getElementById("table");
console.log(table);