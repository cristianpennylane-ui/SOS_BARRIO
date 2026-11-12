<?php
require_once __DIR__.'/../core/Model.php';
class AdminModel extends Model
{
    public function usuarios(): array
    {
        return $this->db->query("SELECT u.id_usuario,u.nombres,u.apellidos,u.dni,u.email,u.estado,u.id_rol,u.id_vecindario, v.nombre AS vecindario,r.nombre AS rol,s.id_serenazgo,s.turno,s.disponible FROM usuarios u JOIN roles r ON r.id_rol=u.id_rol JOIN vecindarios v ON v.id_vecindario=u.id_vecindario LEFT JOIN serenazgo s ON s.dni=u.dni ORDER BY u.id_usuario DESC")->fetchAll();
    }

    public function vecindarios(): array
    {
        return $this->db->query('SELECT id_vecindario,nombre FROM vecindarios ORDER BY nombre')->fetchAll();
    }

    // Al ascender un usuario a Oficial se crea/vincula automáticamente su ficha de serenazgo.
    public function cambiarRolYEstado(int $id, int $rol, string $estado, int $vecindario, string $turno): bool
    {
        if (!in_array($rol, [1,2,3], true) || !in_array($estado,['Activo','Inactivo'], true) || !in_array($turno,['Mañana','Tarde','Noche'],true)) return false;
        $this->db->beginTransaction();
        try {
            $st = $this->db->prepare('SELECT id_usuario,nombres,apellidos,dni,telefono,id_rol,estado FROM usuarios WHERE id_usuario=? FOR UPDATE');
            $st->execute([$id]);$u=$st->fetch();
            if (!$u) { $this->db->rollBack();return false; }
            if ((int)$u['id_rol']===1 && $u['estado']==='Activo' && ($rol!==1 || $estado!=='Activo')) {
                $adminActivos=(int)$this->db->query("SELECT COUNT(*) FROM usuarios WHERE id_rol=1 AND estado='Activo'")->fetchColumn();
                if ($adminActivos <= 1) { $this->db->rollBack();return false; }
            }
            $st=$this->db->prepare('UPDATE usuarios SET id_rol=?,estado=?,id_vecindario=? WHERE id_usuario=?');
            $st->execute([$rol,$estado,$vecindario,$id]);
            $this->actualizarFicha($u,$rol,$estado,$vecindario,$turno);
            $this->db->commit(); return true;
        } catch (Throwable $e) { if ($this->db->inTransaction()) $this->db->rollBack(); throw $e; }
    }

    public function crearOficial(array $d): bool
    {
        $this->db->beginTransaction();
        try {
            $st=$this->db->prepare('SELECT 1 FROM usuarios WHERE dni=? OR email=? LIMIT 1');
            $st->execute([$d['dni'],$d['email']]);
            if ($st->fetch()) { $this->db->rollBack();return false; }
            $st=$this->db->prepare("INSERT INTO usuarios (nombres,apellidos,dni,email,password,telefono,direccion,id_vecindario,id_rol,estado) VALUES (?,?,?,?,?,?,?, ?,3,'Activo')");
            $st->execute([$d['nombres'],$d['apellidos'],$d['dni'],$d['email'],password_hash($d['password'],PASSWORD_DEFAULT),$d['telefono'], 'Dependencia de serenazgo',$d['id_vecindario']]);
            $this->actualizarFicha($d,3,'Activo',(int)$d['id_vecindario'],$d['turno']);
            $this->db->commit();return true;
        } catch (Throwable $e) { if ($this->db->inTransaction()) $this->db->rollBack(); throw $e; }
    }

    private function actualizarFicha(array $u, int $rol, string $estado, int $zona, string $turno): void
    {
        $st=$this->db->prepare('SELECT id_serenazgo FROM serenazgo WHERE dni=? LIMIT 1');
        $st->execute([$u['dni']]); $f=$st->fetch();
        if ($rol===3) {
            if ($f) {
                $st=$this->db->prepare('UPDATE serenazgo SET nombres=?,apellidos=?,telefono=?,id_vecindario=?,turno=?,disponible=? WHERE id_serenazgo=?');
                $st->execute([$u['nombres'],$u['apellidos'],$u['telefono'] ?? '',$zona,$turno,$estado==='Activo'?1:0,$f['id_serenazgo']]);
            } else {
                $st=$this->db->prepare('INSERT INTO serenazgo (nombres,apellidos,dni,telefono,id_vecindario,turno,disponible) VALUES (?,?,?,?,?,?,?)');
                $st->execute([$u['nombres'],$u['apellidos'],$u['dni'],$u['telefono'] ?? '',$zona,$turno,$estado==='Activo'?1:0]);
            }
        } elseif ($f) {
            // Una cuenta que deja de ser oficial no podrá recibir asignaciones automáticas.
            $st=$this->db->prepare('UPDATE serenazgo SET disponible=0 WHERE id_serenazgo=?');
            $st->execute([$f['id_serenazgo']]);
        }
    }
}
