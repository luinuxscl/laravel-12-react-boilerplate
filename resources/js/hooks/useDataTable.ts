import { useCallback, useEffect, useMemo, useState } from 'react';

export type UseDataTableOptions = {
  initialPage?: number;
  initialPerPage?: number;
};

export function useDataTable({ initialPage = 1, initialPerPage = 10 }: UseDataTableOptions = {}) {
  const [page, setPage] = useState(initialPage);
  const [perPage, setPerPage] = useState(initialPerPage);
  const [search, setSearch] = useState('');
  const [sortBy, setSortBy] = useState('id');
  const [sortDir, setSortDir] = useState<'asc' | 'desc'>('desc');

  const query = useMemo(() => ({ page, perPage, search, sortBy, sortDir }), [page, perPage, search, sortBy, sortDir]);

  const setSort = useCallback((key: string) => {
    setSortDir((d) => (key === sortBy ? (d === 'asc' ? 'desc' : 'asc') : 'asc'));
    setSortBy(key);
  }, [sortBy]);

  useEffect(() => {
    setPage(1); // reset page on search or sort change
  }, [search, sortBy, sortDir]);

  return { page, perPage, search, sortBy, sortDir, setPage, setPerPage, setSearch, setSort, query };
}

export default useDataTable;
