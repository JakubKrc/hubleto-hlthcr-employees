import Table from '@hubleto/react-ui/components/fc/Table'
import { type TableMeta, type TableProps } from '@hubleto/react-ui/components/fc/TableInterfaces'
import FormEmployee from './FormEmployee'

interface TableEmployeesProps extends TableProps {
  idBranch?: number,
}

const componentName = 'TableEmployees'
const parentApp = 'Hubleto/App/External/WaiBlue/HlthcrEmployees'

// Computed (virtual) columns that hold a date: shown in the same format as the other dates.
const VIRTUAL_DATE_COLUMNS = ['virt_last_exam_date', 'virt_exam_valid_to'];

function renderEmployeeCell(table: TableMeta, columnName: string, column: any, data: any, options: any) {
  const isShownAsDate = VIRTUAL_DATE_COLUMNS.includes(columnName) && !options.renderEditor;
  if (isShownAsDate) {
    return table.renderDefaultCell(columnName, {...column, type: 'date'}, data, options);
  }
  return table.renderDefaultCell(columnName, column, data, options);
}

const TableEmployees = (props: TableEmployeesProps) => {
  const idBranch = props.idBranch ?? 0;
  return <Table
    componentName={componentName}
    parentApp={parentApp}
    model={parentApp + '/Models/Employee'}
    baseUrlSlug='hlthcr-employees/employees'
    endpointParams={{idBranch: idBranch}}
    formDefaultValues={idBranch > 0 ? {id_branch: idBranch} : {}}
    formModalProps={{type: 'right wide'}}
    renderForm={(table: TableMeta) => <FormEmployee {...table.getDefaultFormProps()} />}
    renderCell={renderEmployeeCell}
    {...props}
  />;
}

export default TableEmployees;
