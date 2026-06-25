<?php
// 1. Configurar cabeceras para responder en formato JSON al HTML
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");

// 2. Definir la ruta absoluta hacia tu base de datos de Access
// Al estar en la misma carpeta del proyecto, usamos la ruta de XAMPP.
$db_path = "C:\\xampp\\htdocs\\dulce_aroma\\cafeteria_dulce_aroma1.accdb";

// Verificar que el archivo realmente exista en esa ruta
if (!file_exists($db_path)) {
    echo json_encode(["status" => "error", "mensaje" => "Error: No se encontró el archivo de la base de datos en la ruta especificada."]);
    exit;
}

try {
    // 3. Conexión a la base de datos usando el Driver de ODBC para Access
    $dsn = "odbc:Driver={Microsoft Access Driver (*.mdb, *.accdb)};Dbq=$db_path;Uid=Admin;Pwd=;";
    $conexion = new PDO($dsn);
    $conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 4. Leer los datos enviados en formato JSON desde el JavaScript del HTML
    $json_data = file_get_contents("php://input");
    $orden = json_decode($json_data, true);

    if (!$orden) {
        echo json_encode(["status" => "error", "mensaje" => "No se recibieron datos válidos en el pedido."]);
        exit;
    }

    // 5. Mapear y limpiar las variables del JSON
    $cliente = $orden['cliente'];
    $correo = $orden['correo'];
    $telefono = $orden['telefono'];
    $entrega = $orden['entrega'];
    $direccion = ($entrega === 'envio') ? $orden['direccion'] : 'Mostrador';
    $metodo_pago = $orden['metodo'];
    
    // Quitamos el signo de "$" del total para almacenar solo el valor numérico
    $total = str_replace('$', '', $orden['total']); 
    $fecha = date("Y-m-d H:i:s");

    // 6. Insertar los datos en la tabla 'PEDIDOS' de tu archivo Access
    // NOTA: Asegúrate de que los nombres de las columnas coincidan exactamente con tu Access (ej. Nombre_Cliente, Correo, etc.)
    $sql = "INSERT INTO PEDIDOS (Nombre_Cliente, Correo, Telefono, Metodo_Entrega, Direccion, Metodo_Pago, Total, Fecha_Pedido) 
            VALUES (:cliente, :correo, :telefono, :entrega, :direccion, :metodo_pago, :total, :fecha)";
    
    $stmt = $conexion->prepare($sql);
    $stmt->execute([
        ':cliente' => $cliente,
        ':correo' => $correo,
        ':telefono' => $telefono,
        ':entrega' => $entrega,
        ':direccion' => $direccion,
        ':metodo_pago' => $metodo_pago,
        ':total' => $total,
        ':fecha' => $fecha
    ]);

    // 7. Si la inserción es correcta, respondemos con éxito al HTML
    echo json_encode([
        "status" => "success", 
        "mensaje" => "¡El pedido se ha registrado exitosamente en la base de datos de Dulce Aroma!"
    ]);

} catch (PDOException $e) {
    // Si ocurre un error en el proceso, se lo notificamos detalladamente al HTML
    echo json_encode([
        "status" => "error", 
        "mensaje" => "Error de conexión o consulta con Access: " . $e->getMessage()
    ]);
}
?>