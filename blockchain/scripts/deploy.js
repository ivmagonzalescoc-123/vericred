// Deploys VeriCred, accredits the demo school wallet, and writes the
// connection details Laravel needs to storage/app/vericred-chain.json.
const fs = require("fs");
const path = require("path");
const hre = require("hardhat");

async function main() {
  const [admin, school] = await hre.ethers.getSigners();
  const schoolName = process.env.ISSUER_NAME || "Demo University";

  const VeriCred = await hre.ethers.getContractFactory("VeriCred", admin);
  const contract = await VeriCred.deploy();
  await contract.waitForDeployment();

  await (await contract.registerIssuer(school.address, schoolName)).wait();

  const iface = contract.interface;
  const selector = (name) => iface.getFunction(name).selector;
  const network = await hre.ethers.provider.getNetwork();

  const out = {
    rpc_url: hre.network.config.url || "http://127.0.0.1:8545",
    chain_id: Number(network.chainId),
    contract: await contract.getAddress(),
    admin: admin.address,
    issuer: { address: school.address, name: schoolName },
    selectors: {
      issue: selector("issue"),
      revoke: selector("revoke"),
      verify: selector("verify"),
      issuerName: selector("issuerName"),
      isIssuer: selector("isIssuer"),
    },
    deployed_at: new Date().toISOString(),
  };

  const target = path.join(__dirname, "..", "..", "storage", "app", "vericred-chain.json");
  fs.writeFileSync(target, JSON.stringify(out, null, 2));

  console.log(`VeriCred deployed at ${out.contract}`);
  console.log(`Accredited issuer: ${schoolName} (${school.address})`);
  console.log(`Wrote ${target}`);
}

main().catch((err) => {
  console.error(err);
  process.exitCode = 1;
});
