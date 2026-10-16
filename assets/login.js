// El botón cambia únicamente la visibilidad del campo, sin modificar su valor.
const campoPassword = document.getElementById("password");
const botonPassword = document.getElementById("ver-password");

if (campoPassword && botonPassword) {
    botonPassword.addEventListener("click", function () {
        const mostrar = campoPassword.type === "password";
        campoPassword.type = mostrar ? "text" : "password";

        const etiqueta = mostrar ? "Ocultar contraseña" : "Mostrar contraseña";
        botonPassword.setAttribute("aria-label", etiqueta);
        botonPassword.setAttribute("title", etiqueta);
        botonPassword.setAttribute("aria-pressed", String(mostrar));
        botonPassword.querySelector(".mostrar-password").hidden = mostrar;
        botonPassword.querySelector(".ocultar-password").hidden = !mostrar;
    });
}
