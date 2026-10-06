import requests

url = "http://localhost/la-obra-sistema/backend/auth/login.php"

datos = {
    "username": "empleado1",
    "password": ""
}

respuesta = requests.post(url, json=datos)

print("Código HTTP:", respuesta.status_code)
print("Respuesta de la API:")
print(respuesta.json())