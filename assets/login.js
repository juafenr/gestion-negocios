const campoPassword = document.getElementById("password");
const botonPassword = document.getElementById("ver-password");

botonPassword.addEventListener("click", function () {
    const estaOculta = campoPassword.type === "password";

    if (estaOculta) {
        campoPassword.type = "text";
        botonPassword.textContent = "Ocultar contraseña";
    } else {
        campoPassword.type = "password";
        botonPassword.textContent = "Mostrar contraseña";
    }

    botonPassword.setAttribute("aria-pressed", String(estaOculta));
});