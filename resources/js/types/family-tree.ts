export interface TreeNode {
  id: number;
  name: string;
  gender: 'male' | 'female';
  birth_date?: string;
  death_date?: string;
  father_id?: number;
  mother_id?: number;
  spouse_id?: number;
  relation?: string;
  notes?: string;
  info?: Record<string, any>;
  children?: TreeNode[];
  _visited?: boolean;
}

// دالة للتحقق من صحة بيانات العقدة
export function validateTreeNode(node: TreeNode): boolean {
  console.log('Validating tree node:', node);
  
  // التحقق من الحقول المطلوبة
  if (!node.id || !node.name || !node.gender) {
    console.error('Missing required fields:', {
      id: node.id,
      name: node.name,
      gender: node.gender
    });
    return false;
  }
  
  // التحقق من صحة الجنس
  if (node.gender !== 'male' && node.gender !== 'female') {
    console.error('Invalid gender:', node.gender);
    return false;
  }
  
  // التحقق من صحة التواريخ
  if (node.birth_date) {
    const birthDate = new Date(node.birth_date);
    if (isNaN(birthDate.getTime())) {
      console.error('Invalid birth date:', node.birth_date);
      return false;
    }
  }
  
  if (node.death_date) {
    const deathDate = new Date(node.death_date);
    if (isNaN(deathDate.getTime())) {
      console.error('Invalid death date:', node.death_date);
      return false;
    }
  }
  
  // التحقق من صحة معرفات العلاقات
  if (node.father_id && typeof node.father_id !== 'number') {
    console.error('Invalid father_id:', node.father_id);
    return false;
  }
  
  if (node.mother_id && typeof node.mother_id !== 'number') {
    console.error('Invalid mother_id:', node.mother_id);
    return false;
  }
  
  if (node.spouse_id && typeof node.spouse_id !== 'number') {
    console.error('Invalid spouse_id:', node.spouse_id);
    return false;
  }
  
  // التحقق من صحة الأطفال
  if (node.children) {
    if (!Array.isArray(node.children)) {
      console.error('Children must be an array');
      return false;
    }
    
    for (const child of node.children) {
      if (!validateTreeNode(child)) {
        return false;
      }
    }
  }
  
  return true;
}

// دالة للتحقق من صحة بيانات الشجرة
export function validateTreeData(data: TreeNode[]): boolean {
  console.log('Validating tree data:', {
    length: data.length,
    firstItem: data[0],
    lastItem: data[data.length - 1]
  });
  
  if (!Array.isArray(data)) {
    console.error('Tree data must be an array');
    return false;
  }
  
  for (const node of data) {
    if (!validateTreeNode(node)) {
      return false;
    }
  }
  
  return true;
}