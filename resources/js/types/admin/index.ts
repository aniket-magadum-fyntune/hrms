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

export type Department = {
    id: number;
    name: string;
    description: string | null;
    users_count: number;
};

export type Designation = {
    id: number;
    name: string;
    description: string | null;
    max_users: number | null;
    users_count: number;
};

export type OptionItem = {
    id: number;
    name: string;
};

export type AccessUser = {
    id: number;
    name: string;
    email: string;
    department_id: number | null;
    department: string | null;
    designation_id: number | null;
    designation: string | null;
    roles: string[];
    created_at: string | null;
};
