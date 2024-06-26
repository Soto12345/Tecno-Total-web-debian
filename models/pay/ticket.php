<?php
require('../conf/connection.php');
require_once('../../tcpdf/tcpdf.php');

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
// URL para el directorio en tu máquina webdav
$directory_url = "http://10.0.0.4/pdfs/$name_user/";
// Crea una instancia de TCPDF
$pdf = new TCPDF();
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetTitle('Comprobante de Compra');
$pdf->SetMargins(10, 10, 10);
// Inicializa el contenido del PDF
 $html = '
 <!DOCTYPE html>
 <html lang="es">
 <head>
 <meta charset="UTF-8">
 <meta name="viewport" content="width=device-width, initial-scale=1.0">
 <title>Comprobante de Compra</title>
 <style>
     body {
         font-family: Arial, sans-serif;
     }
     h1 {
         color: #333;
         font-size: 24px;
     }
     table {
         width: 100%;
         border-collapse: collapse;
     }
     th, td {
         border: 1px solid #ddd;
         padding: 8px;
         text-align: left;
     }
     th {
         background-color: #f2f2f2;
     }
 </style>
 </head>
 <body>
     <h1>Comprobante de Compra</h1>
     <p><strong>ID de Ticket:</strong> ' . $id_ticket . '</p>
     <p><strong>Fecha:</strong> ' . $new_date . '</p>
     <p><strong>Status:</strong> ' . $status . '</p>
     <p><strong>Cliente:</strong> ' . $name_user . '</p>   
     <table>
         <thead>
             <tr>
                 <th>Producto</th>
                 <th>Cantidad</th>
                 <th>Precio Unitario</th>
                 <th>Total</th>
             </tr>
         </thead>
         <tbody>';

// Calcular el total de la compra
$total = 0;

// Inserción en la tabla ticket
foreach ($_SESSION['CART'] as $index => $product) {
    $product_name = $product['product_name'];
    $product_stock = $product['product_stock'];
    $amount = $product['product_price'];
    $product_total = $product_stock * $amount;
    $total += $product_total;
 // Agrega los datos del producto al HTML del PDF
    // Agregar fila de producto a la tabla
    $html .= "
        <tr>
            <td>$product_name</td>
            <td>$product_stock</td>
            <td>\$$amount</td>
            <td>\$" . number_format($product_total, 2) . "</td>
        </tr>";
    $sql = "INSERT INTO ticket(id_ticket,id_client,nombre_usuario,nombre_producto,cantidad,monto,estado,fecha,email) VALUES('$id_ticket','$id_client','$name_user','$product_name','$product_stock','$amount','$status','$new_date','$email')";
    $connection->run_query($sql);
}
$html .= "
        </tbody>
        <tfoot>
            <tr>
                <td colspan='3' align='right'><strong>Total:</strong></td>
                <td>\$" . number_format($total, 2) . "</td>
            </tr>
        </tfoot>
    </table>";
// Agrega el HTML al PDF
$pdf->AddPage();
$pdf->writeHTML($html, true, false, true, false, '');
// Guarda el PDF en una variable
$pdf_content = $pdf->Output('', 'S');
// Configura la solicitud CURL para enviar el PDF al directorio
$curl = curl_init();
curl_setopt_array($curl, array(
    CURLOPT_URL => $directory_url . $id_ticket . '_comprobante.pdf',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_PUT => true,
    CURLOPT_INFILESIZE => strlen($pdf_content),
    CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
    CURLOPT_USERPWD => "administrador@gmail.com:12345",
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_INFILE => fopen('data://text/plain;base64,' . base64_encode($pdf_content), 'r'),
));
// Ejecuta la solicitud CURL para enviar el PDF al directorio
$response = curl_exec($curl);

// Obtiene el código de respuesta HTTP
$http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);

// Verifica si se envió el PDF correctamente
if ($http_code == 201) {
    echo "PDF enviado con éxito al directorio: $directory_url{$_SESSION['usuario']}/$id_ticket\_comprobante.pdf";
} else {
    echo "Error al enviar el PDF al directorio: $directory_url{$_SESSION['usuario']}/$id_ticket\_comprobante.pdf";
}

// Cierra CURL
curl_close($curl);
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
