import React, { useState, useEffect } from 'react';
import Layout from '@/Layouts/Layout';
import { Card, CardHeader, CardTitle, CardContent } from '@/Components/ui/card';
import DataProviderSelect from '@/Components/ui/data-provider-select';
import RowCountInput from '@/Components/ui/row-count-input';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { useForm, Link } from '@inertiajs/react'; // Import Link
import { Alert, AlertDescription, AlertTitle } from '@/Components/ui/alert';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/Components/ui/table";
import { Badge } from "@/Components/ui/badge";

const Configure = ({ schema, dataProviders }) => {
  const [generationConfig, setGenerationConfig] = useState({});
  const [jobId, setJobId] = useState(null);
  const [jobStatus, setJobStatus] = useState(null);
  const [downloadUrl, setDownloadUrl] = useState(null);
  const [jobError, setJobError] = useState(null);

  const { data, setData, post, processing, errors } = useForm({
    format: 'sql', // Default format
    seed: '',      // Optional seed
    tables: {},    // This will be populated from generationConfig
  });

  useEffect(() => {
    if (schema && schema.tables) {
      const initialConfig = {};
      schema.tables.forEach(table => {
        initialConfig[table.name] = {
          rowCount: 1000,
          columns: {},
        };
        table.columns.forEach(column => {
          initialConfig[table.name].columns[column.name] = {
            provider: '', // Default empty provider
            options: {},
          };
        });
      });
      setGenerationConfig(initialConfig);
    }
  }, [schema]);

  // Update form data when generationConfig changes
  useEffect(() => {
    setData('tables', generationConfig);
  }, [generationConfig]);

  // Polling for job status
  useEffect(() => {
    let interval;
    if (jobId && jobStatus !== 'completed' && jobStatus !== 'failed') {
      interval = setInterval(async () => {
        try {
            const response = await fetch(route('generate.job', { job_id: jobId }));
            const result = await response.json();
            setJobStatus(result.status);
            if (result.status === 'completed') {
                setDownloadUrl(result.download_url);
                clearInterval(interval);
            } else if (result.status === 'failed') {
                setJobError(result.error || 'Job failed unexpectedly.');
                clearInterval(interval);
            }
        } catch (error) {
            setJobError('Failed to fetch job status.');
            clearInterval(interval);
        }
      }, 5000); // Poll every 5 seconds
    }

    return () => clearInterval(interval); // Cleanup on unmount or if jobId/jobStatus changes
  }, [jobId, jobStatus]);

    const handleRowCountChange = (tableName, event) => {
        const count = parseInt(event.target.value, 10) || 0;
        setGenerationConfig(prevConfig => ({
            ...prevConfig,
            [tableName]: {
                ...prevConfig[tableName],
                rowCount: count,
            },
        }));
    };

  const handleProviderChange = (tableName, columnName, providerKey) => {
    setGenerationConfig(prevConfig => ({
      ...prevConfig,
      [tableName]: {
        ...prevConfig[tableName],
        columns: {
          ...prevConfig[tableName].columns,
          [columnName]: {
            ...prevConfig[tableName].columns[columnName],
            provider: providerKey,
          },
        },
      },
    }));
  };

  const handleSubmit = (e) => {
    e.preventDefault();
    setJobId(null); // Reset job status
    setJobStatus(null);
    setDownloadUrl(null);
    setJobError(null);

    post(route('generate.store'), {
      ...data,
      onSuccess: (page) => {
        const { job_id, status } = page.props.flash.job;
        setJobId(job_id);
        setJobStatus(status);
      },
      onError: (errors) => {
        setJobError('Error starting generation: ' + Object.values(errors).flat().join(', '));
      },
    });
  };

  const getJobAlertVariant = () => {
    switch (jobStatus) {
      case 'completed':
        return 'default';
      case 'failed':
        return 'destructive';
      default:
        return null;
    }
  };

  return (
    <>
      <h1 className="text-3xl font-extrabold tracking-tight lg:text-4xl mb-6">Configure Schema</h1>
      <form onSubmit={handleSubmit} className="space-y-6">
        <div className="flex items-center space-x-4">
          <label htmlFor="format" className="text-sm font-medium leading-none">Format:</label>
          <Select
            value={data.format}
            onValueChange={(value) => setData('format', value)}
          >
            <SelectTrigger className="w-[120px]">
              <SelectValue placeholder="Select format" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="sql">SQL</SelectItem>
              <SelectItem value="csv">CSV</SelectItem>
            </SelectContent>
          </Select>

          <label htmlFor="seed" className="text-sm font-medium leading-none">Seed (Optional):</label>
          <Input
            id="seed"
            type="number"
            value={data.seed}
            onChange={(e) => setData('seed', e.target.value)}
            placeholder="Random Seed"
            className="w-[150px]"
          />
        </div>

        {jobStatus && (
          <Alert variant={getJobAlertVariant()}>
            <AlertTitle>Job Status: {jobStatus}</AlertTitle>
            <AlertDescription>
              {jobError && (
                <div className="flex items-center justify-between">
                  <p>{jobError}</p>
                  <Button onClick={handleSubmit} className="ml-4" variant="outline">Retry</Button>
                </div>
              )}
              {jobStatus === 'pending' && <p>Data generation is in progress...</p>}
              {jobStatus === 'completed' && downloadUrl && (
                <p>Job completed! <Link href={downloadUrl} className="font-bold hover:underline">Download Data</Link></p>
              )}
            </AlertDescription>
          </Alert>
        )}

        {schema.tables.map((table) => (
          <Card key={table.name}>
            <CardHeader>
              <CardTitle className="flex justify-between items-center">
                {table.name}
                <div className="flex items-center space-x-2">
                  <span className="text-sm text-gray-500">Rows:</span>
                  <RowCountInput
                    value={generationConfig[table.name]?.rowCount || 0}
                    onChange={(e) => handleRowCountChange(table.name, e)}
                  />
                </div>
              </CardTitle>
            </CardHeader>
            <CardContent>
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>Column</TableHead>
                    <TableHead>Data Type</TableHead>
                    <TableHead>Constraints</TableHead>
                    <TableHead className="text-right">Data Provider</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                {table.columns.map((column) => (
                  <TableRow key={column.name}>
                    <TableCell className="font-medium">{column.name}</TableCell>
                    <TableCell>{column.dataType}</TableCell>
                    <TableCell>
                      {column.isPrimaryKey && <Badge variant="outline">PK</Badge>}
                      {column.isForeignKey && <Badge variant="outline" className="ml-1">FK</Badge>}
                    </TableCell>
                    <TableCell className="text-right">
                      <DataProviderSelect
                        value={generationConfig[table.name]?.columns[column.name]?.provider || ''}
                        onChange={(providerKey) => handleProviderChange(table.name, column.name, providerKey)}
                        dataProviders={dataProviders}
                      />
                    </TableCell>
                  </TableRow>
                ))}
                </TableBody>
              </Table>
            </CardContent>
          </Card>
        ))}
        <Button type="submit" disabled={processing || (jobStatus === 'pending')}>
          {processing || jobStatus === 'pending' ? 'Generating...' : 'Generate Data'}
        </Button>
      </form>
    </>
  );
};

Configure.layout = page => <Layout children={page} />;

export default Configure;
