export interface TreeNode {
  id: number;
  name: string;
  gender: 'male' | 'female';
  birth_date?: string | null;
  death_date?: string | null;
  father_id?: number | null;
  mother_id?: number | null;
  children: TreeNode[];
  info?: {
    address?: string | null;
    phone?: string | null;
    email?: string | null;
    occupation?: string | null;
    education?: string | null;
    notes?: string | null;
  } | null;
  _visited?: boolean;
}