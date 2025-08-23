import React from 'react';

export type Column<T> = {
  key: keyof T | string;
  header: string;
  render?: (row: T) => React.ReactNode;
};

export type DataTableProps<T> = {
  columns: Column<T>[];
  data: T[];
  loading?: boolean;
  total?: number;
  page?: number;
  perPage?: number;
  onPageChange?: (page: number) => void;
  onPerPageChange?: (perPage: number) => void;
  onSearch?: (term: string) => void;
};

export function DataTable<T>({ columns, data, loading }: DataTableProps<T>) {
  if (loading) {
    return <div className="p-4 text-sm text-muted-foreground">Loading...</div>;
  }

  return (
    <div className="w-full overflow-x-auto">
      <table className="w-full text-left text-sm">
        <thead>
          <tr className="border-b">
            {columns.map((c) => (
              <th key={String(c.key)} className="px-3 py-2 font-medium">
                {c.header}
              </th>
            ))}
          </tr>
        </thead>
        <tbody>
          {data.map((row: any, i) => (
            <tr key={i} className="border-b hover:bg-muted/30">
              {columns.map((c) => (
                <td key={String(c.key)} className="px-3 py-2">
                  {c.render ? c.render(row) : String(row[c.key as keyof typeof row] ?? '')}
                </td>
              ))}
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}

export default DataTable;
