<?php

namespace App\Enums;

enum Role: string
{
    /** Gestiona usuarios y credenciales SAT. Tiene acceso a todo. */
    case Admin = 'admin';

    /** Usa las cinco pantallas SAT con la credencial que tenga asignada. */
    case Operador = 'operador';

    /** Solo lectura: transacciones, manifiestos y bitácora. */
    case Auditor = 'auditor';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrador',
            self::Operador => 'Operador',
            self::Auditor => 'Auditor',
        };
    }

    /**
     * Permisos concedidos por este rol. El admin no se lista aquí: se resuelve
     * con Gate::before, de modo que un permiso nuevo no se le olvida a nadie.
     *
     * @return array<int, Permission>
     */
    public function permissions(): array
    {
        return match ($this) {
            self::Admin => Permission::cases(),
            self::Operador => [
                Permission::SatValidarNit,
                Permission::SatValidarCuscar,
                Permission::SatAgregarCuscar,
                Permission::SatConsultarManifiesto,
                Permission::SatCambiarClave,
            ],
            self::Auditor => [
                Permission::TransaccionesView,
                Permission::BitacoraView,
            ],
        };
    }

    public function hasPermission(Permission $permission): bool
    {
        return in_array($permission, $this->permissions(), true);
    }

    /** @return array<string, string> value => label, para los <select> */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $r) => [$r->value => $r->label()])
            ->all();
    }
}
