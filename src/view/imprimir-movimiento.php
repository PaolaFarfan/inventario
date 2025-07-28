<?php
// 1. VALIDACIÓN DE PARÁMETROS DE URL
// Dividir la URL en segmentos usando "/" como separador
$ruta = explode("/", $_GET['views']);
// Verificar que exista el segundo segmento (ID del movimiento)
if (!isset($ruta[1]) || $ruta[1] == "") {
    // Si no hay ID, redirigir a la página principal de movimientos
    header("location: " . BASE_URL . "movimientos");
    exit;
}

// 2. PETICIÓN cURL PARA OBTENER DATOS DEL MOVIMIENTO
// Inicializar sesión cURL
$curl = curl_init();
// Configurar opciones de cURL
curl_setopt_array($curl, array(
    // URL del endpoint con parámetros de autenticación y datos
    CURLOPT_URL => BASE_URL_SERVER . "src/control/Movimiento.php?tipo=buscar_movimiento_id&sesion=" . $_SESSION['sesion_id'] . "&token=" . $_SESSION['sesion_token'] . "&data=$ruta[1]",
    CURLOPT_RETURNTRANSFER => true,    // Devolver resultado como string
    CURLOPT_FOLLOWLOCATION => true,    // Seguir redirecciones
    CURLOPT_ENCODING => "",            // Permitir todas las codificaciones
    CURLOPT_MAXREDIRS => 10,          // Máximo 10 redirecciones
    CURLOPT_TIMEOUT => 30,            // Timeout de 30 segundos
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1, // Usar HTTP 1.1
    CURLOPT_CUSTOMREQUEST => "GET",   // Método HTTP GET
    CURLOPT_HTTPHEADER => array(      // Cabeceras HTTP
        "x-rapidapi-host: " . BASE_URL_SERVER,
        "x-rapidapi-key: XXXX"
    ),
));
// Ejecutar la petición
$response = curl_exec($curl);
// Capturar posibles errores
$err = curl_error($curl);
// Cerrar sesión cURL
curl_close($curl);

// Verificar si hubo errores en la petición
if ($err) {
    echo "cURL Error #:" . $err;
    exit;
}

// Decodificar respuesta JSON a objeto PHP
$respuesta = json_decode($response);

// 3. CONSTRUCCIÓN DEL CONTENIDO HTML PARA EL PDF
// Crear el HTML con estilos CSS embebidos
$contenido_pdf = '
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Papeleta de Rotación de Bienes</title>
  <style>
    /* Estilos CSS para el documento */
    body {
      background-color: white;
      color: #1e1e1e;
      font-family: Arial, sans-serif;
      padding: 30px;
    }
    h2 {
      text-align: center;
      margin-bottom: 30px;
    }
    .datos {
      margin-bottom: 20px;
    }
    .datos p {
      margin: 5px 0;
    }
    table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 30px;
    }
    th, td {
      border: 1px solid #1e1e1e;
      padding: 8px;
      text-align: center;
    }
    .firmas {
      display: flex;
      justify-content: space-between;
      margin-top: 50px;
    }
    .firmas div {
      text-align: center;
      width: 45%;
    }
    .ubicacion {
      text-align: right;
      margin-top: 20px;
    }
  </style>
</head>
<body>

  <h2>PAPELETA DE ROTACIÓN DE BIENES</h2>

  <div class="datos">
    <!-- Información estática y dinámica del movimiento -->
    <p><strong>ENTIDAD:</strong> DIRECCION REGIONAL DE EDUCACION - AYACUCHO</p>
    <p><strong>AREA:</strong> OFICINA DE ADMINISTRACIÓN</p>
    <p><strong>ORIGEN:</strong> ' . $respuesta->amb_origen->codigo . ' - ' . $respuesta->amb_origen->detalle . '</p>
    <p><strong>DESTINO:</strong> ' . $respuesta->amb_destino->codigo . ' - ' . $respuesta->amb_destino->detalle . '</p>
    <p><strong>MOTIVO (*):</strong> ' . $respuesta->movimiento->descripcion . '</p>
  </div>

  <table>
    <thead>
      <tr>
        <!-- Cabeceras de la tabla -->
        <th>ITEM</th>
        <th>CÓDIGO PATRIMONIAL</th>
        <th>NOMBRE DEL BIEN</th>
        <th>MARCA</th>
        <th>COLOR</th>
        <th>MODELO</th>
        <th>ESTADO</th>
      </tr>
    </thead>
    <tbody>';

// 4. GENERAR FILAS DE LA TABLA CON LOS BIENES
$contador = 1;
// Iterar sobre cada bien en el detalle del movimiento
foreach ($respuesta->detalle as $bien) {
    // Construir cada fila de la tabla con los datos del bien
    $contenido_pdf .= "<tr>";
    $contenido_pdf .= "<td>" . $contador . "</td>";                    // Número de item
    $contenido_pdf .= "<td>" . $bien->cod_patrimonial . "</td>";       // Código patrimonial
    $contenido_pdf .= "<td>" . $bien->denominacion . "</td>";          // Nombre del bien
    $contenido_pdf .= "<td>" . $bien->marca . "</td>";                 // Marca
    $contenido_pdf .= "<td>" . $bien->color . "</td>";                 // Color
    $contenido_pdf .= "<td>" . $bien->modelo . "</td>";                // Modelo
    $contenido_pdf .= "<td>" . $bien->estado . "</td>";                // Estado
    $contenido_pdf .= "</tr>";
    $contador++; // Incrementar contador para el siguiente item
}

// Cerrar la tabla
$contenido_pdf .= '</tbody>
  </table>';

// 5. FORMATEAR FECHA EN ESPAÑOL
// Crear objeto DateTime desde la fecha de registro del movimiento
$fechaMovimiento = new DateTime($respuesta->movimiento->fecha_registro);
// Array con nombres de meses en español
$meses = [
    1 => 'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio',
    'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'
];
// Extraer día, mes y año
$dia = $fechaMovimiento->format('d');
$mes = $meses[(int)$fechaMovimiento->format('m')];
$anio = $fechaMovimiento->format('Y');

// 6. AGREGAR UBICACIÓN, FECHA Y FIRMAS AL HTML
$contenido_pdf .= "
<div class='ubicacion'>
  <p>Ayacucho, $dia de $mes del $anio</p>
</div>

<div class='firmas'>
  <div>
    <p>------------------------------</p>
    <p>ENTREGUÉ CONFORME</p>
  </div>
  <div>
    <p>------------------------------</p>
    <p>RECIBÍ CONFORME</p>
  </div>
</div>

</body>
</html>
";

// 7. CARGAR LIBRERÍA TCPDF
require_once('./vendor/tecnickcom/tcpdf/tcpdf.php');


// 8. CREAR CLASE PERSONALIZADA PARA ENCABEZADO Y PIE DE PÁGINA
class MYPDF extends TCPDF {
    public function Header() {
        // URL de las imágenes
        $logo_left  = 'https://iestphuanta.edu.pe/';
        $logo_right = 'https://dreayacucho.gob.pe/storage/directory/lCcjIpyYl7E5tQjWegZVLZvp1ZIMbY-metaWk9PRUEybXNRUGlYWWtKRng0SkxqcG9SRW5jTEZuLW1ldGFiRzluYnk1d2JtYz0tLndlYnA=-.webp';

        // Logo izquierdo
        $this->Image($logo_left, 15, 10, 25);  
        // Logo derecho
        $this->Image($logo_right, 170, 10, 25); 

        // Título principal 
        $this->SetXY(55, 12); // desplazado al centro 
        $this->SetFont('helvetica', 'B', 11);
        $this->SetTextColor(0, 0, 0); //negro
        $this->Cell(100, 6, 'INSTITUTO DE EDUCACIÓN SUPERIOR TECNOLÓGICO PÚBLICO', 0, 1, 'C');

        // Subtítulo
        $this->SetX(55);
        $this->SetFont('helvetica', 'B', 11);
        $this->SetTextColor(0, 70, 140); // Gris oscuro
        $this->Cell(100, 6, '"HUANTA"', 0, 1, 'C');

         // contenido
        $this->SetX(55, 12);
        $this->SetFont('helvetica', 'I', 10);
        $this->SetTextColor(85, 85, 85); // Gris oscuro
        $this->Cell(100, 6, 'Sistema de Control Patrimonial', 0, 1, 'C');

        // Línea decorativa azul
        $this->SetDrawColor(52, 152, 219);
        $this->SetLineWidth(0.8);
        $this->Line(15, 44, 195, 44);

        $this->Ln(5); // Espacio adicional
    }

  public function Footer() {
    // Posicionar a 15 mm del final de la página
    $this->SetY(-15);

    // Línea superior del footer
    $this->SetDrawColor(189, 195, 199);
    $this->SetLineWidth(0.5);
    $this->Line(15, $this->GetY() - 5, 195, $this->GetY() - 5);

    // Estilo del texto
    $this->SetFont('helvetica', 'I', 8);
    $this->SetTextColor(100, 100, 100);

    // Texto de número de página centrado
    $this->Cell(0, 10, 'Página ' . $this->getAliasNumPage() . ' de ' . $this->getAliasNbPages(), 0, 0, 'C');

    // Reset de estilo
    $this->SetTextColor(0, 0, 0);
    $this->SetLineWidth(0.2);

    }
}



// 9. CREAR INSTANCIA DEL PDF Y CONFIGURAR PROPIEDADES
$pdf = new MYPDF(); // Usar nuestra clase personalizada
// Configurar información del documento
$pdf->SetCreator(PDF_CREATOR);                           // Creador del PDF
$pdf->SetAuthor('Paola Astrid');                       // Autor del documento
$pdf->SetTitle('Reporte de movimientos - ' . date('d/m/Y')); // Título con fecha
$pdf->SetSubject('Papeleta de Rotación de Bienes');     // Asunto
$pdf->SetKeywords('movimiento, bienes, patrimonial, rotacion'); // Palabras clave

// 10. CONFIGURAR MÁRGENES Y PÁGINA
// Márgenes ajustados para dar espacio al encabezado personalizado
$pdf->SetMargins(PDF_MARGIN_LEFT, 45, PDF_MARGIN_RIGHT); // Margen superior 45mm
$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);                // Margen del encabezado
$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);                // Margen del pie

// Configurar salto de página automático
$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
// Configurar fuente por defecto
$pdf->SetFont('helvetica', '', 10);
// Agregar nueva página
$pdf->AddPage();

// 11. INSERTAR CONTENIDO HTML EN EL PDF
// Convertir HTML a PDF y renderizarlo
$pdf->writeHTML($contenido_pdf, true, false, true, false, '');

// 12. GENERAR Y MOSTRAR EL PDF
// Generar archivo PDF con nombre único (incluye fecha y hora)
$pdf->Output('reporte_movimiento_' . date('Ymd_His') . '.pdf', 'I');
?>