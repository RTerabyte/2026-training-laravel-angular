export interface Sale {
  id: string;
  restaurant_id: string;
  order_id: string;
  user_id: string;
  ticket_number: number | null;
  value_date: string;
  total: number;
  created_at: string;
  updated_at: string;
}

export interface SaleLine {
  id: string;
  restaurant_id: string;
  sale_id: string;
  order_line_id: string;
  user_id: string;
  quantity: number;
  price: number;
  tax_percentage: number;
  created_at: string;
  updated_at: string;
}

export interface SaleLinesResponse {
  sale_lines: SaleLine[];
}