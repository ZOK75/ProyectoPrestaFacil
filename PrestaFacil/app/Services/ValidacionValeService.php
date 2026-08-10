<?php

namespace App\Services;

use App\Models\Configuracion;
use App\Models\Prestamo;
use App\Models\User;
use Carbon\Carbon;

class ValidacionValeService
{
    /**
     * Valida si un Prevale puede ser entregado a un cliente.
     * Realiza todas las validaciones de negocio.
     *
     * @return array Errores encontrados (array vacío si todo es válido)
     */
    public function validarEntregaPrevale(Prestamo $prestamo, User $distribuidora): array
    {
        $errores = [];

        // 1. Vale no cancelado
        if ($this->verificarValeCancelado($prestamo)) {
            $errores[] = "Este prevale fue cancelado o desactivado.";
        }

        // 2. Cliente no tiene vale activo sin liquidar
        $valeActivo = $this->verificarValeActivoCliente($prestamo->cliente_id);
        // Excluimos el prestamo actual de la validación
        if ($valeActivo && $valeActivo->id !== $prestamo->id) {
            $errores[] = "El cliente ya tiene un vale activo sin liquidar (Referencia: {$valeActivo->referencia}).";
        }

        // 3. Distribuidora no morosa
        if ($this->esMorosa($distribuidora)) {
            $errores[] = "La distribuidora está bloqueada por morosidad.";
        }

        // 4. Productos múltiplos de $100
        if (!$this->verificarMultiploProducto((float)$prestamo->monto_prestamo)) {
            $config = Configuracion::actual();
            $errores[] = "El monto del prevale debe ser múltiplo de $" . number_format($config->multiplo_producto, 2) . ".";
        }

        // 5. Regla del % sobre límite de crédito
        $reglaErrores = $this->verificarReglaPorcentaje((float)$prestamo->monto_prestamo, $distribuidora, $prestamo);
        $errores = array_merge($errores, $reglaErrores);

        return $errores;
    }

    /**
     * Valida si un Vale Digital puede ser entregado.
     */
    public function validarEntregaVale(Prestamo $prestamo, User $distribuidora): array
    {
        $errores = [];

        if ($this->verificarValeCancelado($prestamo)) {
            $errores[] = "Este vale fue cancelado o desactivado.";
        }

        $valeActivo = $this->verificarValeActivoCliente($prestamo->cliente_id);
        if ($valeActivo && $valeActivo->id !== $prestamo->id) {
            $errores[] = "El cliente ya tiene un vale activo sin liquidar (Referencia: {$valeActivo->referencia}).";
        }

        if ($this->esMorosa($distribuidora)) {
            $errores[] = "La distribuidora está bloqueada por morosidad.";
        }
        
        // En vale digital la regla del porcentaje SÓLO aplica si hubo un incremento de crédito
        // Para simplificar, aplicamos la misma lógica de porcentaje
        $reglaErrores = $this->verificarReglaPorcentaje((float)$prestamo->monto_prestamo, $distribuidora, $prestamo, true);
        $errores = array_merge($errores, $reglaErrores);

        return $errores;
    }

    /**
     * Clasifica el tipo de pago basándose en la fecha global de corte/pago
     * Retorna: 'anticipado', 'a_tiempo', 'tardio'
     */
    public function determinarTipoPago(Carbon $fechaPagoRealizada): string
    {
        $config = Configuracion::actual();
        
        $fechaCorte = $config->fecha_corte;
        $fechaLimite = $config->fecha_limite_pago;
        $fechaPago2 = $config->fecha_pago_2;

        // Simplificación de la lógica para el MVP:
        // Si pagó antes del corte -> anticipado
        // Si pagó entre corte y límite -> a_tiempo
        // Si pagó después del límite -> tardio
        
        if ($fechaPagoRealizada->startOfDay()->lt($fechaCorte->startOfDay())) {
            return 'anticipado';
        }
        
        if ($fechaPagoRealizada->startOfDay()->lte($fechaLimite->startOfDay())) {
            return 'a_tiempo';
        }

        return 'tardio';
    }

    /**
     * Verifica si una distribuidora alcanzó el límite de strikes por morosidad
     */
    public function esMorosa(User $distribuidora): bool
    {
        return $distribuidora->esMorosa();
    }

    /**
     * Calcula los puntos ganados por un pago
     */
    public function calcularPuntosAbono(string $tipoPago): int
    {
        if ($tipoPago === 'anticipado') {
            return Configuracion::actual()->puntos_por_relacion;
        }
        return 0;
    }

    /**
     * Aplica la penalización de morosidad a los puntos
     */
    public function aplicarPenalizacionMorosidad(User $distribuidora): int
    {
        $config = Configuracion::actual();
        $puntosActuales = $distribuidora->puntos;
        
        if ($puntosActuales <= 0) return 0;

        $porcentajePenalizacion = $config->penalizacion_morosidad_puntos / 100.0;
        $puntosPerdidos = (int) ceil($puntosActuales * $porcentajePenalizacion);
        
        return $puntosPerdidos;
    }

    /**
     * Verifica la regla del porcentaje sobre límite de crédito
     */
    public function verificarReglaPorcentaje(float $montoPrestamo, User $distribuidora, Prestamo $prestamo, bool $soloSiIncremento = false): array
    {
        $errores = [];
        $limiteUsar = (float)$distribuidora->limite_credito;
        $huboIncremento = false;

        // Si el límite cambió respecto al guardado en el prestamo cuando se creó
        if ($prestamo->limite_credito_anterior && $prestamo->limite_credito_anterior < $distribuidora->limite_credito) {
            $huboIncremento = true;
            // La regla aplica sobre el crédito anterior
            $limiteUsar = (float)$prestamo->limite_credito_anterior;
        }

        if ($soloSiIncremento && !$huboIncremento) {
            return []; // En vales digitales, si no hubo incremento, no aplica la regla de tope
        }

        $config = Configuracion::actual();
        $porcentaje = $config->obtenerPorcentajeRegla() / 100.0;
        $tolerancia = $config->obtenerTolerancia();

        $maximoPermitido = ($limiteUsar * $porcentaje) + $tolerancia;

        if ($montoPrestamo > $maximoPermitido) {
            $errores[] = "El monto de $" . number_format($montoPrestamo, 2) . " supera el tope permitido de $" . number_format($maximoPermitido, 2) . " (Regla: " . $config->porcentaje_regla_prevale . "% del límite + $" . number_format($tolerancia, 2) . " de tolerancia).";
        }

        return $errores;
    }

    /**
     * Verifica si el vale está cancelado o desactivado
     */
    public function verificarValeCancelado(Prestamo $prestamo): bool
    {
        return $prestamo->estaCancelado();
    }

    /**
     * Verifica si el cliente tiene un vale activo
     */
    public function verificarValeActivoCliente(int $clienteId): ?Prestamo
    {
        return Prestamo::where('cliente_id', $clienteId)
            ->where('estado', 'activo')
            ->first();
    }

    /**
     * Verifica múltiplos de producto
     */
    public function verificarMultiploProducto(float $monto): bool
    {
        $config = Configuracion::actual();
        $multiplo = $config->multiplo_producto;
        
        if ($multiplo <= 0) return true;

        return fmod($monto, $multiplo) == 0;
    }
}
