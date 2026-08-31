<?php

namespace App\Enums;

enum Permission: string
{
    case SatValidarNit = 'sat.validar-nit';
    case SatValidarCuscar = 'sat.validar-cuscar';
    case SatAgregarCuscar = 'sat.agregar-cuscar';
    case SatConsultarManifiesto = 'sat.consultar-manifiesto';
    case SatCambiarClave = 'sat.cambiar-clave';
    case CredencialesManage = 'credenciales.manage';
    case UsuariosManage = 'usuarios.manage';
    case BitacoraView = 'bitacora.view';
    case TransaccionesView = 'transacciones.view';

    public function label(): string
    {
        return match ($this) {
            self::SatValidarNit => 'Validar NIT',
            self::SatValidarCuscar => 'Validar cuscar',
            self::SatAgregarCuscar => 'Agregar cuscar',
            self::SatConsultarManifiesto => 'Consultar manifiesto',
            self::SatCambiarClave => 'Cambiar clave SAT',
            self::CredencialesManage => 'Administrar credenciales SAT',
            self::UsuariosManage => 'Administrar usuarios',
            self::BitacoraView => 'Ver bitácora',
            self::TransaccionesView => 'Ver transacciones SAT',
        };
    }
}
