<?php
require_once __DIR__ . '/../core/Model.php';

/** Registro con aprobación administrativa: la solicitud nunca confiere permisos. */
class SolicitudAccesoModel extends Model
{
    public function tienePendiente(string $dni, string $email): bool
    {
        $st = $this->db->prepare("SELECT 1 FROM solicitudes_acceso WHERE estado='Pendiente' AND (dni=? OR email=?) LIMIT 1");
        $st->execute([$dni, $email]);
        return (bool) $st->fetchColumn();
    }

    public function crearSolicitud(array $d): void
    {
        // Ante solicitudes simultáneas la restricción de registro queda respaldada
        // por comprobaciones en el servidor y la revisión administrativa.
        $st = $this->db->prepare("INSERT INTO solicitudes_acceso
            (rol_solicitado, nombres, apellidos, dni, email, password_hash, telefono,
             direccion, id_vecindario, turno, institucion, justificacion)
            VALUES (:rol, :nombres, :apellidos, :dni, :email, :hash, :telefono,
                    :direccion, :zona, :turno, :institucion, :justificacion)");
        $st->execute([
            ':rol' => $d['rol_solicitado'],
            ':nombres' => $d['nombres'],
            ':apellidos' => $d['apellidos'],
            ':dni' => $d['dni'],
            ':email' => $d['email'],
            ':hash' => password_hash($d['password'], PASSWORD_DEFAULT),
            ':telefono' => $d['telefono'],
            ':direccion' => $d['direccion'],
            ':zona' => $d['id_vecindario'],
            ':turno' => $d['turno'],
            ':institucion' => $d['institucion'],
            ':justificacion' => $d['justificacion'],
        ]);
    }

    public function contarPendientes(): int
    {
        return (int)$this->db->query("SELECT COUNT(*) FROM solicitudes_acceso WHERE estado='Pendiente'")->fetchColumn();
    }

    public function pendientes(): array
    {
        $sql = "SELECT s.id_solicitud, s.rol_solicitado, s.nombres, s.apellidos, s.dni, s.email,
                       s.telefono, s.direccion, s.turno, s.institucion, s.justificacion,
                       s.fecha_solicitud, v.nombre AS vecindario
                FROM solicitudes_acceso s JOIN vecindarios v ON v.id_vecindario=s.id_vecindario
                WHERE s.estado='Pendiente' ORDER BY s.fecha_solicitud ASC, s.id_solicitud ASC";
        return $this->db->query($sql)->fetchAll();
    }

    /** Solo el Admin autenticado puede revisar. Nunca se confía en datos de rol del POST. */
    public function resolver(int $idSolicitud, int $idAdmin, string $decision, bool $identidadVerificada): string
    {
        if (!in_array($decision, ['aprobar','rechazar'], true)) return 'invalid';
        if ($decision === 'aprobar' && !$identidadVerificada) return 'verify';

        $this->db->beginTransaction();
        try {
            $st = $this->db->prepare('SELECT * FROM solicitudes_acceso WHERE id_solicitud=? FOR UPDATE');
            $st->execute([$idSolicitud]);
            $s = $st->fetch();
            if (!$s || $s['estado'] !== 'Pendiente') {
                $this->db->rollBack();
                return 'processed';
            }
            if (!in_array((int)$s['rol_solicitado'], [1,3], true)) {
                $this->db->rollBack();
                return 'invalid';
            }
            if ($decision === 'aprobar') {
                $st = $this->db->prepare('SELECT 1 FROM usuarios WHERE dni=? OR email=? LIMIT 1');
                $st->execute([$s['dni'], $s['email']]);
                if ($st->fetchColumn()) {
                    $this->db->rollBack();
                    return 'duplicate';
                }
                if (!$s['password_hash']) {
                    $this->db->rollBack();
                    return 'invalid';
                }
                $st = $this->db->prepare("INSERT INTO usuarios
                    (nombres,apellidos,dni,email,password,telefono,direccion,id_vecindario,id_rol,estado)
                    VALUES (?,?,?,?,?,?,?,?,?,'Activo')");
                $st->execute([
                    $s['nombres'], $s['apellidos'], $s['dni'], $s['email'], $s['password_hash'],
                    $s['telefono'], $s['direccion'], $s['id_vecindario'], $s['rol_solicitado']
                ]);
                if ((int)$s['rol_solicitado'] === 3) {
                    // Un oficial nuevo debe tener ficha de serenazgo para trabajar en el panel.
                    $st = $this->db->prepare('SELECT id_serenazgo FROM serenazgo WHERE dni=? LIMIT 1');
                    $st->execute([$s['dni']]);
                    $ficha = $st->fetch();
                    if ($ficha) {
                        $st = $this->db->prepare('UPDATE serenazgo SET nombres=?,apellidos=?,telefono=?,id_vecindario=?,turno=?,disponible=1 WHERE id_serenazgo=?');
                        $st->execute([$s['nombres'],$s['apellidos'],$s['telefono'],$s['id_vecindario'],$s['turno'],$ficha['id_serenazgo']]);
                    } else {
                        $st = $this->db->prepare('INSERT INTO serenazgo (nombres,apellidos,dni,telefono,id_vecindario,turno,disponible) VALUES (?,?,?,?,?,?,1)');
                        $st->execute([$s['nombres'],$s['apellidos'],$s['dni'],$s['telefono'],$s['id_vecindario'],$s['turno']]);
                    }
                }
            }
            // No conservamos el hash de contraseña de solicitudes ya resueltas.
            $st = $this->db->prepare("UPDATE solicitudes_acceso
                SET estado=?, fecha_revision=NOW(), id_revisor=?, password_hash=NULL
                WHERE id_solicitud=?");
            $st->execute([$decision === 'aprobar' ? 'Aprobada':'Rechazada', $idAdmin, $idSolicitud]);
            $this->db->commit();
            return $decision === 'aprobar' ? 'approved' : 'rejected';
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $e;
        }
    }
}
