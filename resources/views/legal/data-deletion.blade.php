@extends('legal.layout')

@section('title', 'Data Deletion Instructions')

@section('content')
    <h1>DATA DELETION INSTRUCTIONS</h1>

    <p>APIs Hub does not store your personal data permanently on our servers unless it is strictly necessary for the operation of the services you have explicitly requested (such as your Ad Account IDs or Email for authentication).</p>

    <p>If you wish to delete your activities or personal data from the APIs Hub Application, you can do so by following these simple steps:</p>

    <h2>1. Automated Deletion via Provider Platforms</h2>
    <p>You can request data deletion or revoke access directly from the settings of your connected providers (e.g., Meta/Facebook, Google, etc.). This triggers an automated callback to our systems:</p>
    <ul>
        <li><strong>Deauthorization:</strong> When you remove APIs Hub from your provider's active applications, we immediately wipe the remote node connections and delete your associated credentials from our systems.</li>
        <li><strong>Data Deletion Request:</strong> If you explicitly request data deletion through the provider's platform, APIs Hub automatically creates a <strong>Support Ticket</strong> on your behalf. You will receive a confirmation code linked to this ticket, and our team will review and process the complete data removal within <strong>48-72 hours</strong>.</li>
    </ul>

    <h2>2. Manual Data Deletion Request</h2>
    <p>You can also request data deletion manually at any time using our Support Ticket system:</p>
    <ol>
        <li>Log in to your APIs Hub <strong>Account Portal</strong>.</li>
        <li>Navigate to the <strong>Support Tickets</strong> section.</li>
        <li>Create a new ticket requesting a <strong>Data Deletion</strong>.</li>
        <li>Our team will process your request within <strong>48-72 hours</strong>.</li>
        <li>Once processed, we will permanently delete all your associated records from our databases and revoke any active Access Tokens connected to external APIs.</li>
    </ol>

    <h2>What data will be deleted?</h2>
    <ul>
        <li>All Provider Account associations.</li>
        <li>Provider Access Tokens and Refresh Tokens.</li>
        <li>Cached reporting metrics associated with your account.</li>
        <li>User profile information (Name and Email).</li>
    </ul>

    <h2>Project Suspension and Retention</h2>
    <p>When a project is permanently suspended, APIs Hub maintains the associated data for a safety period of <strong>30 days</strong>. This ensures that data can be recovered in case of accidental suspension or if the user changes their mind.</p>
    <ul>
        <li>After the 30-day period expires, all data is automatically and permanently deleted.</li>
        <li>If a user requests explicit deletion via a Support Ticket, the process is expedited and completed within <strong>24 to 72 hours</strong>.</li>
        <li>User accounts (profiles) are not deleted automatically when a project is suspended; they are only removed upon explicit request via a Support Ticket.</li>
    </ul>

    <h2>Manual Social Logout</h2>
    <p>Additionally, you can always remove APIs Hub's access to your data directly through your APIs Hub project's settings:</p>
    <ol>
        <li>Go to the <strong>Data Sources</strong> section of your project and disconnect the accounts by clicking on the unlink/logout button.</li>
    </ol>

    <p>For any further questions, please contact our support team at <a href="mailto:admin@apis-hub.cloud">admin@apis-hub.cloud</a> or open a Support Ticket.</p>
@endsection
