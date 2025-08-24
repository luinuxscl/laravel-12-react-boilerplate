import React from 'react';

export type Column<T> = {
  key: keyof T | string;
  header: React.ReactNode;
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
  loadingComponent?: React.ReactNode;
  emptyComponent?: React.ReactNode;
};

export function DataTable<T>({ columns, data, loading, loadingComponent, emptyComponent }: DataTableProps<T>) {

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
          {loading && (
            loadingComponent ? (
              <tr>
                <td colSpan={columns.length} className="px-3 py-3">{loadingComponent}</td>
              </tr>
            ) : (
              Array.from({ length: 5 }).map((_, i) => (
                <tr key={`s-${i}`} className="border-b">
                  {columns.map((c, j) => (
                    <td key={`${i}-${j}`} className="px-3 py-2">
                      <div className="h-4 w-full max-w-[140px] animate-pulse rounded bg-muted" />
                    </td>
                  ))}
                </tr>
              ))
            )
          )}

          {!loading && data.length === 0 && (
            <tr>
              <td className="px-3 py-6 text-center text-sm text-muted-foreground" colSpan={columns.length}>
                {emptyComponent ?? 'No records found'}
              </td>
            </tr>
          )}

          {!loading && data.map((row, i) => (
            <tr key={i} className="border-b hover:bg-muted/30">
              {columns.map((c) => (
                <td key={String(c.key)} className="px-3 py-2">
                  {c.render ? c.render(row) : (() => {
                    const key = c.key as keyof typeof row;
                    const value = (row as typeof row)[key];
                    return String(value ?? '');
                  })()}
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
