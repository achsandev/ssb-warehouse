
export interface UserDetail {
  id_karyawan: number;
  nik: string;
  name: string;
  department: string;
  sub_department: string;
  position: string;
  direct_supervisor_id: number;
  direct_supervisor_position: string;
}

export interface User {
  uid?: string;
  name: string;
  email: string;
  password?: string;
  role_id?: number;
  user_details: UserDetail;
}
