<?php
session_start();
require '../config/conexion.php';

// Alerta flash
$flash_error = $_SESSION['error'] ?? null;
$flash_success = $_SESSION['success'] ?? null;
unset($_SESSION['error'], $_SESSION['success']); // Se borran luego de mostrarlas

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
  die("Acceso denegado");
}

if (!isset($_GET['evento_deporte_id'])) {
  die("Evento-Deporte no especificado");
}
$evento_deporte_id = intval($_GET['evento_deporte_id']);

// Obtener el nombre del evento y deporte
$stmt = $conn->prepare("
    SELECT e.nombre AS evento, d.nombre AS deporte
    FROM eventos_deportes ed
    JOIN eventos e ON ed.evento_id = e.id
    JOIN deportes d ON ed.deporte_id = d.id
    WHERE ed.id = :id
");
$stmt->execute(['id' => $evento_deporte_id]);
$info = $stmt->fetch(PDO::FETCH_ASSOC);

$nombreEvento = $info['evento'] ?? 'Evento desconocido';
$nombreDeporte = $info['deporte'] ?? 'Deporte desconocido';

// --- 1) Funciones que usaremos ---

function equiposPorEventoDeporte($evento_deporte_id, $conn) {
  $stmt = $conn->prepare("SELECT id, nombre_equipo FROM equipos WHERE evento_deporte_id = :edid ORDER BY nombre_equipo");
  $stmt->execute(['edid' => $evento_deporte_id]);
  return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function gruposExistentes($evento_deporte_id, $conn) {
  $stmt = $conn->prepare("SELECT * FROM grupos WHERE evento_deporte_id = :edid ORDER BY nombre");
  $stmt->execute(['edid' => $evento_deporte_id]);
  return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function generarGrupos($equipos, $evento_deporte_id, $conn) {
  $numPorGrupo = 4;
  $numGrupos = ceil(count($equipos) / $numPorGrupo);
  for ($i = 0; $i < $numGrupos; $i++) {
    $nombreGrupo = "Grupo " . chr(65 + $i);
    $stmt = $conn->prepare("INSERT INTO grupos (nombre, evento_deporte_id) VALUES (:nombre, :edid)");
$stmt->execute(['nombre' => $nombreGrupo, 'edid' => $evento_deporte_id]);
$grupo_id = $conn->lastInsertId();

    $equiposGrupo = array_slice($equipos, $i * $numPorGrupo, $numPorGrupo);
    foreach ($equiposGrupo as $equipo) {
      $stmt = $conn->prepare("INSERT INTO equipos_grupos (grupo_id, equipo_id) VALUES (:gid, :eid)");
      $stmt->execute(['gid' => $grupo_id, 'eid' => $equipo['id']]);
    }
  }
}

function enfrentamientosExistentes($grupo_id, $conn) {
  $stmt = $conn->prepare("SELECT COUNT(*) FROM enfrentamientos WHERE grupo_id = :gid");
  $stmt->execute(['gid' => $grupo_id]);
  return $stmt->fetchColumn() > 0;
}

function generarEnfrentamientos($grupo_id, $conn) {
  $stmt = $conn->prepare("SELECT equipo_id FROM equipos_grupos WHERE grupo_id = :gid");
  $stmt->execute(['gid' => $grupo_id]);
  $equipos = $stmt->fetchAll(PDO::FETCH_COLUMN);

  for ($i = 0; $i < count($equipos); $i++) {
    for ($j = $i + 1; $j < count($equipos); $j++) {
      $stmt = $conn->prepare("INSERT INTO enfrentamientos (grupo_id, equipo_local_id, equipo_visitante_id) VALUES (:gid, :local, :visitante)");
      $stmt->execute(['gid' => $grupo_id, 'local' => $equipos[$i], 'visitante' => $equipos[$j]]);
    }
  }
}

function tablaPosiciones($grupo_id, $conn) {
  $stmt = $conn->prepare("
    SELECT e.nombre_equipo, tp.*
    FROM tabla_posiciones tp
    JOIN equipos e ON tp.equipo_id = e.id
    WHERE tp.grupo_id = :gid
    ORDER BY tp.puntos DESC, tp.diferencia_goles DESC
  ");
  $stmt->execute(['gid' => $grupo_id]);
  return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function inicializarTablaPosiciones($grupo_id, $conn) {
  $stmt = $conn->prepare("SELECT equipo_id FROM equipos_grupos WHERE grupo_id = :gid");
  $stmt->execute(['gid' => $grupo_id]);
  $equipos = $stmt->fetchAll(PDO::FETCH_COLUMN);

  foreach ($equipos as $equipo_id) {
    $stmt_check = $conn->prepare("SELECT COUNT(*) FROM tabla_posiciones WHERE grupo_id = :gid AND equipo_id = :eid");
    $stmt_check->execute(['gid' => $grupo_id, 'eid' => $equipo_id]);
    if ($stmt_check->fetchColumn() == 0) {
      $stmt_insert = $conn->prepare("INSERT INTO tabla_posiciones (grupo_id, equipo_id) VALUES (:gid, :eid)");
      $stmt_insert->execute(['gid' => $grupo_id, 'eid' => $equipo_id]);
    }
  }
}

function actualizarTablaPosiciones($enfrentamiento_id, $goles_local, $goles_visitante, $conn) {
  // Obtener datos del enfrentamiento
  $stmt = $conn->prepare("SELECT grupo_id, equipo_local_id, equipo_visitante_id, goles_local, goles_visitante FROM enfrentamientos WHERE id = :id");
  $stmt->execute(['id' => $enfrentamiento_id]);
  $partido = $stmt->fetch();

  if (!$partido) return false;

  // Actualizar resultado en enfrentamientos
  $resultado = 'pendiente';
  if ($goles_local > $goles_visitante) $resultado = 'local';
  else if ($goles_local < $goles_visitante) $resultado = 'visitante';
  $resultado = 'definido';

  $stmt = $conn->prepare("UPDATE enfrentamientos SET goles_local = :gl, goles_visitante = :gv, resultado = :res WHERE id = :id");
  $stmt->execute(['gl' => $goles_local, 'gv' => $goles_visitante, 'res' => $resultado, 'id' => $enfrentamiento_id]);

  // Recalcular tabla posiciones: simplificado, resetea todo para el grupo y vuelve a contar
  $grupo_id = $partido['grupo_id'];

  // Resetear tabla posiciones para el grupo
  $stmt = $conn->prepare("UPDATE tabla_posiciones SET partidos_jugados=0, ganados=0, empatados=0, perdidos=0, goles_favor=0, goles_contra=0, diferencia_goles=0, puntos=0 WHERE grupo_id = :gid");
  $stmt->execute(['gid' => $grupo_id]);

  // Obtener todos los enfrentamientos con resultados definitivos
  $stmt = $conn->prepare("SELECT * FROM enfrentamientos WHERE grupo_id = :gid AND resultado != 'pendiente'");
  $stmt->execute(['gid' => $grupo_id]);
  $partidos = $stmt->fetchAll();

  foreach ($partidos as $p) {
    // Local
    $stmt = $conn->prepare("SELECT * FROM tabla_posiciones WHERE grupo_id = :gid AND equipo_id = :eid");
    $stmt->execute(['gid' => $grupo_id, 'eid' => $p['equipo_local_id']]);
    $local = $stmt->fetch();

    // Visitante
    $stmt->execute(['gid' => $grupo_id, 'eid' => $p['equipo_visitante_id']]);
    $visitante = $stmt->fetch();

    // Actualizar partidos jugados
    $stmt = $conn->prepare("UPDATE tabla_posiciones SET partidos_jugados = partidos_jugados + 1, goles_favor = goles_favor + :gf, goles_contra = goles_contra + :gc WHERE grupo_id = :gid AND equipo_id = :eid");

    // Local
    $stmt->execute(['gf' => $p['goles_local'], 'gc' => $p['goles_visitante'], 'gid' => $grupo_id, 'eid' => $p['equipo_local_id']]);
    // Visitante
    $stmt->execute(['gf' => $p['goles_visitante'], 'gc' => $p['goles_local'], 'gid' => $grupo_id, 'eid' => $p['equipo_visitante_id']]);

    // Actualizar ganados/empatados/perdidos y puntos
    // Local
    $ganados = $local['ganados'];
    $empatados = $local['empatados'];
    $perdidos = $local['perdidos'];
    $puntos = $local['puntos'];

    // Visitante
    $gGanados = $visitante['ganados'];
    $gEmpatados = $visitante['empatados'];
    $gPerdidos = $visitante['perdidos'];
    $gPuntos = $visitante['puntos'];

    if ($p['resultado'] === 'local') {
      $ganados++;
      $puntos += 3;
      $gPerdidos++;
    } elseif ($p['resultado'] === 'visitante') {
      $perdidos++;
      $gGanados++;
      $gPuntos += 3;
    } else {
      $empatados++;
      $puntos++;
      $gEmpatados++;
      $gPuntos++;
    }

    // Actualizar tabla posiciones
    $stmt = $conn->prepare("UPDATE tabla_posiciones SET ganados = :g, empatados = :e, perdidos = :p, puntos = :pts WHERE grupo_id = :gid AND equipo_id = :eid");
    $stmt->execute(['g' => $ganados, 'e' => $empatados, 'p' => $perdidos, 'pts' => $puntos, 'gid' => $grupo_id, 'eid' => $p['equipo_local_id']]);

    $stmt->execute(['g' => $gGanados, 'e' => $gEmpatados, 'p' => $gPerdidos, 'pts' => $gPuntos, 'gid' => $grupo_id, 'eid' => $p['equipo_visitante_id']]);

    // Diferencia goles
    $stmt = $conn->prepare("UPDATE tabla_posiciones SET diferencia_goles = goles_favor - goles_contra WHERE grupo_id = :gid");
    $stmt->execute(['gid' => $grupo_id]);
  }

  return true;
}

// --- 2) Control de acciones POST ---

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (isset($_POST['accion'])) {
    if ($_POST['accion'] === 'generar_grupos') {
      $equipos = equiposPorEventoDeporte($evento_deporte_id, $conn);
      generarGrupos($equipos, $evento_deporte_id, $conn);
    }

    if ($_POST['accion'] === 'generar_enfrentamientos' && isset($_POST['grupo_id'])) {
      generarEnfrentamientos(intval($_POST['grupo_id']), $conn);
      inicializarTablaPosiciones(intval($_POST['grupo_id']), $conn);
    }

    if ($_POST['accion'] === 'marcar_jugado' && isset($_POST['enfrentamiento_id'])) {
  $enf_id = intval($_POST['enfrentamiento_id']);

  // Verificar si hay goles ingresados
  $stmt = $conn->prepare("SELECT goles_local, goles_visitante FROM enfrentamientos WHERE id = :id");
  $stmt->execute(['id' => $enf_id]);
  $row = $stmt->fetch();

  if ($row && is_numeric($row['goles_local']) && is_numeric($row['goles_visitante'])) {
    $stmt = $conn->prepare("UPDATE enfrentamientos SET resultado = 'jugado' WHERE id = :id");
    $stmt->execute(['id' => $enf_id]);
    $_SESSION['success'] = "✅ Partido marcado como jugado correctamente.";
  } else {
    $_SESSION['error'] = "❌ Debes ingresar los goles antes de marcar el partido como jugado.";
  }

  // Redirigir para mostrar alerta
  header("Location: fase_grupo.php?evento_deporte_id=$evento_deporte_id");
  exit;
}

    // ✅ Nueva acción: marcar enfrentamiento como jugado (solo si ya hay resultado cargado)
    if ($_POST['accion'] === 'marcar_jugado' && isset($_POST['enfrentamiento_id'])) {
      $enf_id = intval($_POST['enfrentamiento_id']);

      // Verifica que ya se hayan guardado los goles
      $stmt = $conn->prepare("SELECT goles_local, goles_visitante FROM enfrentamientos WHERE id = :id");
      $stmt->execute(['id' => $enf_id]);
      $row = $stmt->fetch();

      if ($row && is_numeric($row['goles_local']) && is_numeric($row['goles_visitante'])) {
        $stmt = $conn->prepare("UPDATE enfrentamientos SET resultado = 'jugado' WHERE id = :id");
        $stmt->execute(['id' => $enf_id]);
      }
    }
  }

  // Redirigir para evitar reenvío de formulario
  header("Location: fase_grupo.php?evento_deporte_id=$evento_deporte_id");
  exit;
}

// --- 3) Mostrar interfaz ---

// Obtener grupos y equipos
$grupos = gruposExistentes($evento_deporte_id, $conn);

?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <title>Fase de Grupos - <?= htmlspecialchars($nombreDeporte) ?> - <?= htmlspecialchars($nombreEvento) ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    function mostrarGrupo(id) {
      document.querySelectorAll('.grupo-contenido').forEach(el => el.classList.add('hidden'));
      document.querySelectorAll('.btn-grupo').forEach(btn => btn.classList.remove('bg-green-600', 'text-white'));
      document.getElementById(id).classList.remove('hidden');
      document.querySelector(`button[data-target="${id}"]`).classList.add('bg-green-600', 'text-white');
    }
  </script>
</head>
<body class="p-5 bg-gray-100">
  <?php if ($flash_error): ?>
  <div class="bg-red-100 text-red-800 px-4 py-2 rounded mb-4 border border-red-300 max-w-3xl mx-auto">
    <?= htmlspecialchars($flash_error) ?>
  </div>
<?php endif; ?>

<?php if ($flash_success): ?>
  <div class="bg-green-100 text-green-800 px-4 py-2 rounded mb-4 border border-green-300 max-w-3xl mx-auto">
    <?= htmlspecialchars($flash_success) ?>
  </div>
<?php endif; ?>

  <h1 class="text-3xl font-extrabold mb-6 text-green-800 text-center">
    🏆 Fase de Grupos: <?= htmlspecialchars($nombreDeporte) ?> - <?= htmlspecialchars($nombreEvento) ?>
  </h1>

  <section class="mb-8 p-4 bg-white rounded shadow">
    <h2 class="text-xl font-semibold mb-4">Equipos registrados</h2>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
      <?php foreach (equiposPorEventoDeporte($evento_deporte_id, $conn) as $equipo): ?>
        <div class="p-2 bg-gray-200 text-center rounded"><?= htmlspecialchars($equipo['nombre_equipo']) ?></div>
      <?php endforeach; ?>
    </div>
  </section>

  <?php if (count($grupos) === 0): ?>
    <form method="POST" class="mb-8 text-center">
      <input type="hidden" name="accion" value="generar_grupos" />
      <button type="submit" class="bg-green-600 text-white px-6 py-3 rounded hover:bg-green-700 text-lg font-semibold">
        Generar Grupos
      </button>
    </form>
  <?php else: ?>

  <div class="flex gap-2 mb-4 justify-center flex-wrap">
    <?php foreach ($grupos as $i => $grupo): ?>
      <?php $gid = "grupo_" . $grupo['id']; ?>
      <button 
        class="btn-grupo px-4 py-2 rounded border border-green-600 font-semibold hover:bg-green-600 hover:text-white <?= $i === 0 ? 'bg-green-600 text-white' : '' ?>" 
        data-target="<?= $gid ?>" 
        type="button" 
        onclick="mostrarGrupo('<?= $gid ?>')"
      >
        <?= htmlspecialchars($grupo['nombre']) ?>
      </button>
    <?php endforeach; ?>
  </div>

  <?php foreach ($grupos as $i => $grupo): ?>
    <?php
      $gid = "grupo_" . $grupo['id'];
      $stmt = $conn->prepare("SELECT e.nombre_equipo FROM equipos e JOIN equipos_grupos eg ON e.id = eg.equipo_id WHERE eg.grupo_id = :gid");
      $stmt->execute(['gid' => $grupo['id']]);
      $equipos = $stmt->fetchAll(PDO::FETCH_COLUMN);

      $stmt = $conn->prepare("SELECT ef.*, el.nombre_equipo AS local_nombre, ev.nombre_equipo AS visitante_nombre FROM enfrentamientos ef JOIN equipos el ON ef.equipo_local_id = el.id JOIN equipos ev ON ef.equipo_visitante_id = ev.id WHERE ef.grupo_id = :gid ORDER BY ef.id");
      $stmt->execute(['gid' => $grupo['id']]);
      $enfrentamientos_raw = $stmt->fetchAll(PDO::FETCH_ASSOC);

      $enfrentamientos_por_fecha = [];
      $equipos_jugando_fecha = [];
      $jornada_num = 1;
      foreach ($enfrentamientos_raw as $ef) {
    $local = $ef['local_nombre'];
    $visitante = $ef['visitante_nombre'];

    // Buscar fecha disponible donde ninguno de los dos equipos haya jugado aún
    $fecha_disponible = null;
    for ($i = 1; $i <= $jornada_num; $i++) {
        $fecha_nombre = "Fecha " . $i;
        $equipos_en_fecha = $equipos_jugando_fecha[$fecha_nombre] ?? [];

        if (!in_array($local, $equipos_en_fecha) && !in_array($visitante, $equipos_en_fecha)) {
            $fecha_disponible = $fecha_nombre;
            break;
        }
    }

    // Si no encontró una fecha existente válida, crea una nueva
    if (!$fecha_disponible) {
        $jornada_num++;
        $fecha_disponible = "Fecha " . $jornada_num;
    }

    // Agrega el enfrentamiento a la fecha
    $enfrentamientos_por_fecha[$fecha_disponible][] = $ef;

    // Marca equipos como usados en esa fecha
    $equipos_jugando_fecha[$fecha_disponible][] = $local;
    $equipos_jugando_fecha[$fecha_disponible][] = $visitante;
}

      inicializarTablaPosiciones($grupo['id'], $conn);
      $tabla = tablaPosiciones($grupo['id'], $conn);
    ?>

    <section id="<?= $gid ?>" class="grupo-contenido <?= $i === 0 ? '' : 'hidden' ?> p-6 bg-white rounded shadow mb-8">
      <h2 class="text-2xl font-bold mb-3">Grupo: <?= htmlspecialchars($grupo['nombre']) ?></h2>
      <p class="mb-4 font-semibold text-gray-700"><strong>Equipos:</strong> <?= implode(", ", $equipos) ?></p>

      <?php if (empty($enfrentamientos_raw)): ?>
        <form method="POST" class="mb-4">
          <input type="hidden" name="accion" value="generar_enfrentamientos" />
          <input type="hidden" name="grupo_id" value="<?= $grupo['id'] ?>" />
          <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
            Generar Enfrentamientos
          </button>
        </form>
      <?php else: ?>

        <?php foreach ($enfrentamientos_por_fecha as $fecha => $enfrentamientos): ?>
          <h3 class="mt-6 font-semibold text-lg text-indigo-700 border-b border-indigo-300 pb-1"><?= htmlspecialchars($fecha) ?></h3>
          <table class="w-full mt-2 border-collapse border border-gray-300 text-center">
            <thead>
              <tr class="bg-indigo-100">
                <th class="border border-gray-300 p-2">Local</th>
                <th class="border border-gray-300 p-2">Goles Local</th>
                <th class="border border-gray-300 p-2">-</th>
                <th class="border border-gray-300 p-2">Goles Visitante</th>
                <th class="border border-gray-300 p-2">Visitante</th>
                <th class="border border-gray-300 p-2">Actualizar Resultado</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($enfrentamientos as $ef): ?>
    <tr>
      <td class="border border-gray-300 p-2"><?= htmlspecialchars($ef['local_nombre']) ?></td>
      <td class="border border-gray-300 p-2">
        <form method="POST" class="inline">
          <input type="hidden" name="enfrentamiento_id" value="<?= $ef['id'] ?>" />
          <input type="number" name="goles_local" value="<?= $ef['goles_local'] ?>" min="0" class="w-16 text-center border rounded" required <?= ($ef['resultado'] !== 'pendiente') ? 'readonly' : '' ?>>
      </td>
      <td class="border border-gray-300 p-2">-</td>
      <td class="border border-gray-300 p-2">
          <input type="number" name="goles_visitante" value="<?= $ef['goles_visitante'] ?>" min="0" class="w-16 text-center border rounded" required <?= ($ef['resultado'] !== 'pendiente') ? 'readonly' : '' ?>>
      </td>
      <td class="border border-gray-300 p-2"><?= htmlspecialchars($ef['visitante_nombre']) ?></td>
      <td class="border border-gray-300 p-2">
        <?php if ($ef['resultado'] === 'jugado'): ?>
          <button disabled class="bg-gray-500 text-white px-2 py-1 rounded w-full cursor-not-allowed">Partido Jugado</button>
        <?php elseif ($ef['resultado'] === 'definido'): ?>
          <input type="hidden" name="accion" value="marcar_jugado" />
          <button type="submit" class="bg-blue-600 text-white px-2 py-1 rounded hover:bg-blue-700 w-full">
            Marcar Jugado
          </button>
        <?php else: ?>
          <input type="hidden" name="accion" value="actualizar_resultado" />
          <button type="submit" class="bg-green-600 text-white px-2 py-1 rounded hover:bg-green-700 w-full">
            Guardar
          </button>
        <?php endif; ?>
        </form>
      </td>
    </tr>
  <?php endforeach; ?>

            </tbody>
          </table>
        <?php endforeach; ?>

        <h3 class="font-semibold text-xl mb-2 mt-6">Tabla de Posiciones</h3>
        <table class="w-full border-collapse border border-gray-300 text-center">
          <thead>
            <tr class="bg-gray-200">
              <th class="border border-gray-300 p-2">Equipo</th>
              <th class="border border-gray-300 p-2">PJ</th>
              <th class="border border-gray-300 p-2">G</th>
              <th class="border border-gray-300 p-2">E</th>
              <th class="border border-gray-300 p-2">P</th>
              <th class="border border-gray-300 p-2">GF</th>
              <th class="border border-gray-300 p-2">GC</th>
              <th class="border border-gray-300 p-2">DG</th>
              <th class="border border-gray-300 p-2">Pts</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($tabla as $row): ?>
            <tr>
              <td class="border border-gray-300 p-2"><?= htmlspecialchars($row['nombre_equipo']) ?></td>
              <td class="border border-gray-300 p-2"><?= $row['partidos_jugados'] ?></td>
              <td class="border border-gray-300 p-2"><?= $row['ganados'] ?></td>
              <td class="border border-gray-300 p-2"><?= $row['empatados'] ?></td>
              <td class="border border-gray-300 p-2"><?= $row['perdidos'] ?></td>
              <td class="border border-gray-300 p-2"><?= $row['goles_favor'] ?></td>
              <td class="border border-gray-300 p-2"><?= $row['goles_contra'] ?></td>
              <td class="border border-gray-300 p-2"><?= $row['diferencia_goles'] ?></td>
              <td class="border border-gray-300 p-2"><?= $row['puntos'] ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>

      <?php endif; ?>
    </section>
  <?php endforeach; ?>
  <?php endif; ?>
</body>
</html>
