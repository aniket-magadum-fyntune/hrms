export type AccessRole = {
    id: number;
    name: string;
    permissions: string[];
    users_count: number;
    is_system: boolean;
    is_protected: boolean;
    is_controlled: boolean;
    can_update: boolean;
    can_delete: boolean;
};

export type AccessPermission = {
    id: number;
    name: string;
    roles_count: number;
    users_count: number;
};

export type AccessUser = {
    id: number;
    name: string;
    email: string;
    roles: string[];
    created_at: string | null;
};
