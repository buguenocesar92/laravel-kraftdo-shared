<?php

namespace Kraftdo\Shared\Auditoria;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Spatie\Permission\Models\Role;

/**
 * Política del Resource de Roles del panel (Filament + Filament Shield).
 *
 * No decide nada por dominio: cada método delega en el permiso homónimo que
 * Filament Shield genera y muestra como checkbox en la pantalla de Roles
 * (`view_any_role`, `create_role`, …), así que no hay ningún nombre de rol
 * (`super_admin` vs `administrador`) que hardcodear acá — eso lo resuelve
 * `Gate::before` en cada sistema consumidor, fuera de esta política.
 */
class RolePolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('view_any_role');
    }

    public function view(AuthUser $authUser, Role $role): bool
    {
        return $authUser->can('view_role');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_role');
    }

    public function update(AuthUser $authUser, Role $role): bool
    {
        return $authUser->can('update_role');
    }

    public function delete(AuthUser $authUser, Role $role): bool
    {
        return $authUser->can('delete_role');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('delete_any_role');
    }

    public function restore(AuthUser $authUser, Role $role): bool
    {
        return $authUser->can('restore_role');
    }

    public function forceDelete(AuthUser $authUser, Role $role): bool
    {
        return $authUser->can('force_delete_role');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('force_delete_any_role');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('restore_any_role');
    }

    public function replicate(AuthUser $authUser, Role $role): bool
    {
        return $authUser->can('replicate_role');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('reorder_role');
    }
}
