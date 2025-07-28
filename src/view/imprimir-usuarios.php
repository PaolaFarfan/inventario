<?php

    $curl = curl_init(); //inicia la sesión cURL
    curl_setopt_array($curl, array(
        CURLOPT_URL => BASE_URL_SERVER."src/control/Usuario.php?tipo=listarUsuarios&sesion=".$_SESSION['sesion_id']."&token=".$_SESSION['sesion_token'],//url a la que se conecta
        CURLOPT_RETURNTRANSFER => true, //devuelve el resultado como una cadena del tipo curl_exec
        CURLOPT_FOLLOWLOCATION => true, //sigue el encabezado que le envíe el servidor
        CURLOPT_ENCODING => "", // permite decodificar la respuesta y puede ser"identity", "deflate", y "gzip", si está vacío recibe todos los disponibles.
        CURLOPT_MAXREDIRS => 10, // Si usamos CURLOPT_FOLLOWLOCATION le dice el máximo de encabezados a seguir
        CURLOPT_TIMEOUT => 30, // Tiempo máximo para ejecutar
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1, // usa la versión declarada
        CURLOPT_CUSTOMREQUEST => "GET", // el tipo de petición, puede ser PUT, POST, GET o Delete dependiendo del servicio
        CURLOPT_HTTPHEADER => array(
            "x-rapidapi-host: ".BASE_URL_SERVER,
            "x-rapidapi-key: XXXX"
        ), //configura las cabeceras enviadas al servicio
    )); //curl_setopt_array configura las opciones para una transferencia cURL

    $response = curl_exec($curl); // respuesta generada
    $err = curl_error($curl); // muestra errores en caso de existir

    curl_close($curl); // termina la sesión 

    if ($err) {
        echo "cURL Error #:" . $err; // mostramos el error
    } else {
        $respuesta = json_decode($response);
        $contenido = $respuesta->usuarios;
       // print_r($respuesta);
      $contenido_pdf = '';
     $contenido_pdf = '
           <!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Lista de usuarios</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      margin: 40px;
    }

    h2 {
      text-align: center;
      text-transform: uppercase;
    }

    .info {
      margin-bottom: 20px;
    }

    .info p {
      margin: 5px 0;
    }

    .info span.label {
      font-weight: bold;
      display: inline-block;
      width: 100px;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 20px;
    }

    table, th, td {
      border: 1px solid black;
    }

    th, td {
      text-align: center;
      padding: 5px;
    }

    .firma-container {
      margin-top: 80px;
      display: flex;
      justify-content: space-between;
      padding: 0 40px;
    }

    .firma {
      text-align: center;
    }

    .fecha {
      text-align: right;
      margin-top: 30px;
    }
       table {
    border-collapse: collapse;
    width: 100%;
    font-size: 9pt;
  }
  th, td {
    border: 1px solid #000;
    padding: 4px;
    text-align: center;
  }
  thead th {
    background-color: #f2f2f2;
    font-weight: bold;

  </style>
</head>
<body>

  <h2>LISTA DE USUARIOS</h2>

  <table>
    <thead>
      <tr>
        <th>ITEM</th>
        <th>DNI</th>
        <th>NOMBRE TY APELLIDOS</th>
        <th>CORREO</th>
        <th>TELEFONO</th>
        <th>ESTADO</th>
        <th>FECHA REGISTRO</th>
      </tr>
    </thead>
      <tbody>
      ';
 
    $contador = 1;
    foreach ($contenido as $usuario) {
       $contenido_pdf .= "<tr>";
        $contenido_pdf .=  "<td>" . $contador . "</td>";
        $contenido_pdf .=  "<td>" . $usuario->dni . "</td>";
        $contenido_pdf .=  "<td>" . $usuario->nombres_apellidos . "</td>";
        $contenido_pdf .=  "<td>" . $usuario->correo . "</td>";
        $contenido_pdf .=  "<td>" . $usuario->telefono . "</td>";
        $contenido_pdf .=  "<td>" . $usuario->estado . "</td>";
        $contenido_pdf .=  "<td>" . $usuario->fecha_registro . "</td>";
        $contenido_pdf .=  "</tr>";
        $contador +=1;
    }
      
            $meses = [
                1 => 'enero',
                2 => 'febrero',
                3 => 'marzo',
                4 => 'abril',
                5 => 'mayo',
                6 => 'junio',
                7 => 'julio',
                8 => 'agosto',
                9 => 'septiembre',
                10 => 'octubre',
                11 => 'noviembre',
                12 => 'diciembre'
            ];

            $dia = date('d');
            $mes = $meses[(int)date('m')];
            $anio = date('Y');

            $contenido_pdf .= "Ayacucho, $dia de $mes del $anio";

$contenido_pdf .= '
    </tbody>
  </table>



  <div class="firma-container">
    <div class="firma">
      <p>------------------------------</p>
      <p>ENTREGUÉ CONFORME</p>
    </div>
    <div class="firma">
      <p>------------------------------</p>
      <p>RECIBÍ CONFORME</p>
    </div>
  </div>

</body>
</html>
';  
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
        $this->SetTextColor(0, 0, 0);       // Negro
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


$pdf = new MYPDF();
// set document information
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Paola');
$pdf->SetTitle('listar usuarios');

// 10. CONFIGURAR MÁRGENES Y PÁGINA
$pdf->SetMargins(PDF_MARGIN_LEFT, 45, PDF_MARGIN_RIGHT); 
$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);                
$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);  

// Configurar salto de página automático
$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

// Configurar fuente por defecto
$pdf->SetFont('helvetica', '', 8);

// Agregar nueva página
$pdf->AddPage();

// 11. INSERTAR CONTENIDO HTML EN EL PDF
// Convertir HTML a PDF y renderizarlo
$pdf->writeHTML($contenido_pdf, true, false, true, false, '');

// 12. GENERAR Y MOSTRAR EL PDF
// Generar archivo PDF con nombre único (incluye fecha y hora)
$pdf->Output('listar_usuarios_' . date('Ymd_His') . '.pdf', 'I');
    }