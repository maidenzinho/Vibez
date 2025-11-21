// Algoritmo de hash utlizado: SHA-256.
document.addEventListener("DOMContentLoaded", function() { // Espera HTML carregar p/ executar.
    const form = document.querySelector("form"); // Form de registro.
    const passwordInput = document.getElementById("password");
    const confirmPasswordInput = document.getElementById("confirm_password");

    form.addEventListener("submit", function(e) { // Roda no ato do envio do formulario ("e" para "event").
        if (passwordInput.value !== confirmPasswordInput.value) {
            e.preventDefault(); // Impede envio do form.
            alert("As senhas não coincidem.");
            return; // Sai sem continuar para o hash.
        }

        // Gera o hash com SHA256 e converte para string hexadecimal.
        const hashedPassword = CryptoJS.SHA256(passwordInput.value).toString();
        passwordInput.value = hashedPassword; // Substitui as senhas digitadas pelo hash.
        confirmPasswordInput.value = hashedPassword;
    });
});
