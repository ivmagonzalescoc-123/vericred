// SPDX-License-Identifier: MIT
pragma solidity ^0.8.24;

/// @title VeriCred credential anchor registry
/// @notice Stores ONLY fingerprints (SHA-256 hashes) of academic credentials.
///         No personal data ever touches the chain.
contract VeriCred {
    struct Record {
        address issuer;   // school wallet that anchored the hash
        uint64 issuedAt;  // block timestamp of anchoring
        bool revoked;
    }

    address public admin;
    mapping(address => bool) public isIssuer;
    mapping(address => string) public issuerName;
    mapping(bytes32 => Record) private records;

    event IssuerRegistered(address indexed issuer, string name);
    event IssuerRemoved(address indexed issuer);
    event CredentialIssued(bytes32 indexed hash, address indexed issuer);
    event CredentialRevoked(bytes32 indexed hash, address indexed issuer);

    modifier onlyAdmin() {
        require(msg.sender == admin, "VeriCred: not admin");
        _;
    }

    constructor() {
        admin = msg.sender;
    }

    /// @notice Accredit a school wallet so it may anchor credentials.
    function registerIssuer(address issuer, string calldata name) external onlyAdmin {
        isIssuer[issuer] = true;
        issuerName[issuer] = name;
        emit IssuerRegistered(issuer, name);
    }

    function removeIssuer(address issuer) external onlyAdmin {
        isIssuer[issuer] = false;
        emit IssuerRemoved(issuer);
    }

    /// @notice Anchor a credential hash. Only accredited schools can call this.
    function issue(bytes32 hash) external {
        require(isIssuer[msg.sender], "VeriCred: not an accredited issuer");
        require(records[hash].issuer == address(0), "VeriCred: already issued");
        records[hash] = Record(msg.sender, uint64(block.timestamp), false);
        emit CredentialIssued(hash, msg.sender);
    }

    /// @notice Revoke a credential. Only the school that issued it can revoke.
    function revoke(bytes32 hash) external {
        require(records[hash].issuer == msg.sender, "VeriCred: not the issuer");
        require(!records[hash].revoked, "VeriCred: already revoked");
        records[hash].revoked = true;
        emit CredentialRevoked(hash, msg.sender);
    }

    /// @notice Public, free lookup used by verifiers.
    function verify(bytes32 hash)
        external
        view
        returns (bool exists, address issuer, uint64 issuedAt, bool revoked)
    {
        Record memory r = records[hash];
        return (r.issuer != address(0), r.issuer, r.issuedAt, r.revoked);
    }
}
