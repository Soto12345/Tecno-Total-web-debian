<?php
require('../conf/connection.php');
require_once('../../tcpdf/tcpdf.php');

// $client = new Sabre\DAV\Client([
//     'baseUri' => 'https://tu-servidor-webdav.com/', // URL base del servidor WebDAV
//     'userName' => 'tu-usuario', // Nombre de usuario para la autenticación en WebDAV
//     'password' => 'tu-contraseña', // Contraseña para la autenticación en WebDAV
// ]);
session_start();
global $connection;
$Client_ID = 'ARaJ_-lZ2bImQWLMJIrYbZu5_n1Vf0uF6ClGgqduTPpf3uRk3NqMH-BU94qh1DQG1a06xZb6fPQt7RDF';
$Secret = 'EKAUavyoaR8O7PjnC-Ee0XATTUcYCAwHzkfPqWt9MX2ESFaVkuCH6C_uMDNTrTBFGJsJp0QU2UTB81Rt';

// Llamada a la página de PayPal para extraer los datos del comprador
$login = curl_init("https://api-m.sandbox.paypal.com/v1/oauth2/token");
curl_setopt($login, CURLOPT_RETURNTRANSFER, True);
curl_setopt($login, CURLOPT_USERPWD, $Client_ID . ":" . $Secret);
curl_setopt($login, CURLOPT_POSTFIELDS, "grant_type=client_credentials");

// Extracción del token de la venta
$answer_login = curl_exec($login);
$answer_object = json_decode($answer_login);
$access_token = $answer_object->access_token;

// Extracción de los datos del comprador
$order = curl_init("https://api-m.sandbox.paypal.com/v1/checkout/orders/" . $_GET['paymentID']);
curl_setopt($order, CURLOPT_HTTPHEADER, array("Content-Type: application/json", "Authorization: Bearer " . $access_token));
curl_setopt($order, CURLOPT_RETURNTRANSFER, True);
$answer_data = curl_exec($order);
$object_answer_data = json_decode($answer_data);
curl_close($login);
curl_close($order);

$id_ticket = $object_answer_data->id;
$amount = $object_answer_data->gross_total_amount->value;
$status = $object_answer_data->status;
$date = $object_answer_data->update_time;
$new_date = date('Y-m-d H:i:s', strtotime($date));
$email = $object_answer_data->payer->email_address;
$id_client = $object_answer_data->payer->payer_id;
$name_user = $_SESSION['usuario'];

// // Inicializar TCPDF
// $pdf = new TCPDF();
// $pdf->SetCreator(PDF_CREATOR);
// $pdf->SetTitle('Comprobante de Compra');
// $pdf->SetMargins(10, 10, 10);
// $pdf->AddPage();
// // Contenido del PDF
// $html = '
// <!DOCTYPE html>
// <html lang="es">
// <head>
// <meta charset="UTF-8">
// <meta name="viewport" content="width=device-width, initial-scale=1.0">
// <title>Comprobante de Compra</title>
// <style>
//     body {
//         font-family: Arial, sans-serif;
//     }
//     h1 {
//         color: #333;
//         font-size: 24px;
//     }
//     table {
//         width: 100%;
//         border-collapse: collapse;
//     }
//     th, td {
//         border: 1px solid #ddd;
//         padding: 8px;
//         text-align: left;
//     }
//     th {
//         background-color: #f2f2f2;
//     }
// </style>
// </head>
// <body>
//     <h1>Comprobante de Compra</h1>
//     <p><strong>ID de Ticket:</strong> ' . $id_ticket . '</p>
//     <p><strong>Fecha:</strong> ' . $new_date . '</p>
//     <p><strong>Status:</strong> ' . $status . '</p>
//     <p><strong>Cliente:</strong> ' . $name_user . '</p>
//     <p><strong>Correo Electrónico:</strong> ' . $email . '</p>
//     <table>
//         <thead>
//             <tr>
//                 <th>Producto</th>
//                 <th>Cantidad</th>
//                 <th>Precio Unitario</th>
//                 <th>Total</th>
//             </tr>
//         </thead>
//         <tbody>';
// $total = 0;
// Inserción en la tabla ticket
foreach ($_SESSION['CART'] as $index => $product) {
    $product_name = $product['product_name'];
    $product_stock = $product['product_stock'];
    $amount = $product['product_price'];
    $sql = "INSERT INTO ticket(id_ticket,id_client,nombre_usuario,nombre_producto,cantidad,monto,estado,fecha,email) VALUES('$id_ticket','$id_client','$name_user','$product_name','$product_stock','$amount','$status','$new_date','$email')";
    $connection->run_query($sql);
    // $html .= '
    //         <tr>
    //             <td>' . $product_name . '</td>
    //             <td>' . $product_stock . '</td>
    //             <td>$' . $amount . '</td>
    //             <td>$' . number_format($amount, 2) . '</td>
    //         </tr>';
    // $total += $amount * $product_stock;
}
// $html .= '
//         </tbody>
//         <tfoot>
//             <tr>
//                 <td colspan="3" align="right"><strong>Total:</strong></td>
//                 <td>$' . number_format($total, 2) . '</td>
//             </tr>
//         </tfoot>
//     </table>
// </body>
// </html>';
// Agregar contenido al PDF y cerrar
// $pdf->writeHTML($html, true, false, true, false, '');

// Guardar el PDF en el servidor
// $pdfFilePath = 'comprobantes/' . $id_ticket . '_comprobante.pdf'; // Ruta donde se guardará el archivo PDF
// $pdf->Output($_SERVER['DOCUMENT_ROOT'] . '/' . $pdfFilePath, 'F');

// Guardar la ruta del PDF en la base de datos u otro sistema de almacenamiento

// Redirigir o mostrar un mensaje de éxito
// echo "El comprobante se ha generado y almacenado correctamente en: " . $pdfFilePath;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css">
    <title>TICKET</title>
</head>

<body>
    <div class="container mt-5">
        <div class="card">
            <div class="card-body">
                <h1 class="display-4">¡PROCESO FINALIZADO!</h1>
                <hr class="my-4">
                <strong>Tu lista de compras:</strong>
                <div class="card mt-3">
                    <div class="card-body">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Status</th>
                                    <th>Nombre</th>
                                    <th>Cantidad</th>
                                    <th>Precio</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $total = 0; ?>
                                <?php foreach ($_SESSION['CART'] as $index => $product) { ?>
                                    <tr>
                                        <td><?php echo $new_date; ?></td>
                                        <td><?php echo $status; ?></td>
                                        <td><?php echo $product['product_name']; ?></td>
                                        <td><?php echo $product['product_stock']; ?></td>
                                        <td>$<?php echo $product['product_price']; ?></td>
                                        <td>$<?php echo number_format($product['product_price'] * $product['product_stock'], 2); ?></td>
                                    </tr>
                                    <?php $total = $total + ($product['product_price'] * $product['product_stock']); ?>
                                <?php } ?>
                                <tr>
                                    <td>
                                        <h3>TOTAL</h3>
                                    </td>
                                    <td>
                                        <h3>$<?php echo number_format($total, 2); ?></h3>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card mt-3">
                    <div class="card-body">
                        <div class="text-center">
                            <a href="../user/sign_off.php" class="btn btn-primary">Finalizar sesión</a>
                            <a href="../../index.php?cart_off=yes" class="btn btn-success">Continuar comprando</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.2.1.slim.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js"></script>
</body>

</html>