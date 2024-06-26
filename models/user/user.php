<?php
require('../conf/connection.php');
require('../conf/utilities.php');

class User_data
{
    //atributos
    private $email_user;
    private $password_user;

    //constructor
    public function __construct()
    {
        $this->email_user = "";
        $this->password_user = "";
    }
    //metodos get y set
    public function setEmail_user($email_user)
    {
        $this->email_user = $email_user;
    }

    public function getEmail_user()
    {
        return $this->email_user;
    }

    public function setPassword_user($password_user)
    {
        $this->password_user = $password_user;
    }

    public function getPassword_user()
    {
        return $this->password_user;
    }
}
//metodo de registrar al usuario
function register_user($email_user, $password_user)
{
    global $connection;

    // Generar un ID aleatorio para el usuario
    $random_string = Get_id();

    // Encriptar la contraseña del usuario
    $hash = password_hash($password_user, PASSWORD_DEFAULT);

    // Consultar si el correo electrónico ya está registrado en la base de datos
    $sql_verification = "SELECT correo FROM usuario WHERE correo='$email_user'";
    $result_verification = $connection->run_query($sql_verification);

    // Verificar si la consulta se realizó correctamente
    if ($result_verification) {
        // Verificar si el correo electrónico ya existe en la base de datos
        if (mysqli_num_rows($result_verification)) {
            echo "El correo electrónico ya está registrado";
            $connection->Close_connection();
        } else {
            // Insertar el nuevo usuario en la base de datos
            $sql_insert = "INSERT INTO usuario VALUES('$random_string','$email_user',1,'$hash')";
            $result_insert = $connection->run_query($sql_insert);

            // Verificar si la inserción en la base de datos fue exitosa
            if ($result_insert) {
                // URL del servidor WebDAV y ruta al archivo pass.dav
                $url = 'http://10.0.0.4/pass.dav';

                // Configurar opciones para cURL para realizar una solicitud GET
                $curl = curl_init($url);
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
                curl_setopt($curl, CURLOPT_USERPWD, 'administrador@gmail.com:12345');

                // Ejecutar la solicitud cURL y capturar la respuesta
                $contenido_actual = curl_exec($curl);

                // Verificar si la solicitud fue exitosa
                if ($contenido_actual !== false) {
                    // Formatear los datos del usuario y contraseña
                    $datos_usuario = "$email_user:$hash";

                    // Concatenar los nuevos datos al contenido existente con un salto de línea
                    $contenido_nuevo = $contenido_actual . PHP_EOL . $datos_usuario;

                    // Configurar opciones para cURL para realizar una solicitud PUT con el contenido actualizado
                    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PUT");
                    curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: text/plain'));
                    curl_setopt($curl, CURLOPT_POSTFIELDS, $contenido_nuevo);

                    // Ejecutar la solicitud cURL para actualizar el archivo pass.dav
                    $respuesta = curl_exec($curl);

                    // Verificar si la actualización fue exitosa
                    if ($respuesta !== false) {
                        echo "Usuario registrado correctamente en el servidor WebDAV: $email_user";
                    } else {
                        echo "Error al registrar el usuario en el servidor WebDAV";
                    }
                } else {
                    echo "Error al leer el contenido del archivo pass.dav";
                }

                // Cerrar la sesión cURL
                curl_close($curl);
                // URL para el directorio en tu m      quina webdav
$directory_url = "http://10.0.0.4/pdfs/$email_user";
// Inicializar CURL
$curl = curl_init();
// Configurar la solicitud CURL
curl_setopt_array($curl, array(
    CURLOPT_URL => $directory_url, // URL para el directorio
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CUSTOMREQUEST => 'MKCOL', // M      todo para crear un directorio
    CURLOPT_HTTPAUTH => CURLAUTH_BASIC, // Tipo de autenticaci      n
    CURLOPT_USERPWD => "administrador@gmail.com:12345", 
    CURLOPT_SSL_VERIFYPEER => false,
));
// Ejecutar la solicitud CURL
$response = curl_exec($curl);

// Obtener el codigo de respuesta HTTP
$http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
// Cerrar CURL
curl_close($curl);
 
                
                // Redirigir al usuario a la página de inicio de sesión
                header('location: ../forms/login.html');
            } else {
                echo "Error al insertar el usuario en la base de datos";
            }
            $connection->Close_connection();
        }
    } else {
        echo "Error en la conexión a la base de datos";
    }
}
//metodo para logear al usuario
function login_user($email_user, $password_user)
{
    global $connection;
    //extraer la contraseña del usuario encriptado
    $sql_encrypted_password = "SELECT contrasena,Tipo_usuario FROM usuario WHERE correo='$email_user'";
    $result = $connection->run_query($sql_encrypted_password);
    //verifica si la solicitud a la base de datos fue enviado con exito
    if ($result) {
        $row = $result->fetch_assoc();
        //verifica que si hay un correo que esta solicitando por el usuario
        if ($row !== NULL && isset($row['contrasena'])) {
            $password_database = $row['contrasena'];
            $type_user = $row['Tipo_usuario'];
            //verifica y desencripta la contraseña y compara si son iguales o no
            if (Verify_password($password_user, $password_database)) {
                session_start();
                $_SESSION['usuario'] = $email_user;
                $_SESSION['Tipo_usuario'] = $type_user;
                header("location: ../../index.php");
            } else {
                ?>
                <script>
                    alert("Usuario o contraseña incorrecta");
                    location = "http://localhost/Tecno-Total-web/models/forms/login.html";
                </script>
                <?php
            }
        } else {
            ?>
            <script>
                alert("Este usuario no existe, favor de registrarse");
                location = "http://localhost/Tecno-Total-web/models/forms/Register.html";
            </script>
            <?php
        }
    } else {
        echo "Error de conexion";
    }
}
?>
