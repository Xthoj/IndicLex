/* nav menu */
function toggleMenu() {
    var x = document.getElementById("nav-links-sub");
    if (x.style.left != "0px") {
      x.style.left = "0px";
      document.body.style.overflowY = "hidden";
    } else {
      x.style.left = "-200px";
      document.body.style.overflowY = "visible";
    }
  }
/* dark/light theme */
function setCookie(name, value, days) {
  let expires = "";
  if (days) {
    const date = new Date();
    date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
    expires = "; expires=" + date.toUTCString();
  }
  document.cookie = name + "=" + value + expires + "; path=/";
}

function getCookie(name) {
  const nameEQ = name + "=";
  const ca = document.cookie.split(';');
  for (let i = 0; i < ca.length; i++) {
    let c = ca[i];
    while (c.charAt(0) === ' ') c = c.substring(1, c.length);
    if (c.indexOf(nameEQ) === 0) return c.substring(nameEQ.length, c.length);
  }
  return null;
}

document.addEventListener("DOMContentLoaded", function () {
  const savedTheme = getCookie("theme");
  if (savedTheme === "dark") {
    document.body.classList.add("dark");
  }
});

document.getElementById("theme-toggle").addEventListener("click", function () {
  document.body.classList.toggle("dark");

  const currentTheme = document.body.classList.contains("dark") ? "dark" : "light";
  setCookie("theme", currentTheme, 365); // Save for 1 year
});