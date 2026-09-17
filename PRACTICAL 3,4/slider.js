let images = [
    "home1.png",
    "home2.png",
    "home3.png"
];
let currentslide = 0;
let slideimage = document.getElementById("slideImage");
function nextslide(){
    if(currentslide>=images.length){
        currentslide = 0;
    }
    currentslide++;
    document.getElementById("slide").src = images[currentslide];
}
function previousslide(){
    currentslide--;
    if(currentslide<0){
        currentslide = images.length - 1;
    }
    document.getElementById("slide").src = images[currentslide];
}
setInterval(nextslide, 3000);