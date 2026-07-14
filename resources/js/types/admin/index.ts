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

export type Employee = {
    id: number;
    employee_code: string;
    name: string;
    work_email: string | null;
    user_id: number | null;
    user_name: string | null;
    user_email: string | null;
    department_id: number | null;
    department: string | null;
    designation_id: number | null;
    designation: string | null;
    manager_id: number | null;
    manager: string | null;
    employment_status: string;
    joined_on: string | null;
};

export type OptionItem = {
    id: number;
    name: string;
};

export type AccessUser = {
    id: number;
    name: string;
    email: string;
    subject_type: string;
    subject_label: string | null;
    roles: string[];
    created_at: string | null;
};
